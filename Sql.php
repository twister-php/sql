<?php
/**
 *	SQL Query Builder
 *
 *	@package     SQL Query Builder
 *	@description Rapidly construct raw SQL query strings
 *	@author      Trevor Herselman <therselman@gmail.com>
 *	@copyright   Copyright (c) 2017 Trevor Herselman
 *	@license     http://opensource.org/licenses/MIT
 *	@link        https://github.com/twister-php/sql
 *
 *	MIT License
 *
 *	Copyright (c) 2017 Trevor Herselman <therselman@gmail.com>
 *
 *	Permission is hereby granted, free of charge, to any person obtaining a copy
 *	of this software and associated documentation files (the "Software"), to deal
 *	in the Software without restriction, including without limitation the rights
 *	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *	copies of the Software, and to permit persons to whom the Software is
 *	furnished to do so, subject to the following conditions:
 *
 *	The above copyright notice and this permission notice shall be included in all
 *	copies or substantial portions of the Software.
 *
 *	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *	SOFTWARE.
 */

class SQL implements \ArrayAccess
{
    /**
     *	The magic starts here!
     *
     *	@var string $sql
     */
	private $sql				=	null;

    /**
     *	The active connection.
     *	Can be either MySQLi/PDO/null.
     *
     *	This variable might not be necessary in future,
	 *		as I can get everything I need once, when you call setConn()/setConnection()
     *
     *	Because I want to find a way around checking the type of connection everytime I want to do something!
     *
     *	@var static mixed $conn
     */
	private	static $conn		=	null;

    /**
     *	NOTE: Not implemented yet! Still deciding what direction to take on string escaping ...
     *
     * The function that handles string escaping.
     * This variable will be overwritten with either:
	 *		`[self::$conn, 'real_escape_string']` for MySQLi connections
	 *			or
	 *		`[self::$conn, 'quote']` for PDO connections
     *
     * @var static escaper
     */
	private	static $escaper		=	'addslashes';	//	call_user_func(self::$escaper, $string)

    /**
     *	NOTE: I'm currently just using the default mb_string character-set!
     *		So whatever your mb_string extention is set to, is the default charset, typically it's UTF-8!
     *
     *	@var static charset
     */
	private	static $charset		=	'utf8';

    /**
     *	Quote style to use for strings. Can change it to "'" or '\'' if you want!
     *
     *	@var quot
     */
	public	static $quot		=	'"';

    /**
     *	Custom data modifiers - with callbacks
     *	  eg.
     *		sql::createModifier(':password', function ($value, $mods) { return md5($value); });
     *		$sql = sql('%s:password', $password);
     *
     *	@var static modifiers
     */
	private	static $modifiers	=	null;

    /**
     *	Custom data modifiers - with callbacks
     *	  eg.
     *		sql::createDataType('password', function ($value, $modifiers) { return '"' . md5($value) . '"'; });
     *		$sql = sql('WHERE password = %password', $password);
     *
     *	@var static modifiers
     */
	private	static $types		=	null;

	/**
	 *	These are used as a fast lookup for the `dynamic` property names in `__get()`;
	 *		they are (currently) NOT used in the function calls, like ->SELECT(),
	 *	eg. SQL()->SELECT_ALL_FROM->users		<<== note that `SELECT_ALL_FROM` is a property!
	 *		__get() uses this list for a fast lookup of replacement values,
	 *			instead of a long (and inefficient for this task) `switch()` statement.
	 *	So basically, this is a list of all the `properties` you can access with replacement text.
	 *	As such, these ARE case-sensitive (because `isset($translations['SELECT'])` is case sensitive!)
	 *		ie. you cannot do `SQL()->select_all_from->users` and expect to get the same results!
	 *	This list is `hard-coded` once and initialized when PHP loads the file, not during script execution.
	 *
	 *	Using the dynamic property technique is purely optional!
	 *	You can write your statements in about 10 different ways with this class!
	 *
     * 	@var static translations
	 */
	public static $translations	=	[	'EXPLAIN'		=>	'EXPLAIN ',
										'SELECT'		=>	'SELECT ',				//	https://dev.mysql.com/doc/refman/5.7/en/select.html
										'DELETE'		=>	'DELETE ',				//	https://dev.mysql.com/doc/refman/5.7/en/delete.html
										'INSERT'		=>	'INSERT ',
										'UPDATE'		=>	'UPDATE ',
										'CALL'			=>	'CALL ',


										'INSERT_INTO'	=>	'INSERT INTO ',			//	or II
										'DELETE_FROM'	=>	'DELETE FROM ',			//	or DF

										//	Note: `ALL` is a reserved SQL keyword, which is the default state of SELECT statements (alternative being DISTINCT)
										//	But I've hijacked the term `ALL` here, because it's more natural for me to say `select all the columns from ...`
										//	If you don't like it, then don't use it!
										'SELECT_ALL'	=>	'SELECT *',
										'SA'			=>	'SELECT *',				//	(S)ELECT (A)LL
										'SALL'			=>	'SELECT *',				//	(S)ELECT (ALL)
										'S_ALL'			=>	'SELECT *',				//	(S)ELECT (ALL)

										'S_CACHE'		=>	'SELECT SQL_CACHE ',
										'S_NCACHE'		=>	'SELECT SQL_NO_CACHE ',
										'S_NO_CACHE'	=>	'SELECT SQL_NO_CACHE ',

										'SELECT_DISTINCT'=>	'SELECT DISTINCT ',
										'SD'			=>	'SELECT DISTINCT ',				//	(S)ELECT (D)ISTINCT
										'SDA'			=>	'SELECT DISTINCT * ',			//	(S)ELECT (D)ISTINCT (A)LL
										'SDCA'			=>	'SELECT DISTINCT COUNT(*) ',	//	(S)ELECT (D)ISTINCT (C)OUNT (A)LL
										'SDCAS'			=>	'SELECT DISTINCT COUNT(*) AS ',	//	(S)ELECT (D)ISTINCT (C)OUNT (A)S
										'SDCAA'			=>	'SELECT DISTINCT COUNT(*) AS ',	//	(S)ELECT (D)ISTINCT (C)OUNT (A)LL (A)S
										'SDCAAS'		=>	'SELECT DISTINCT COUNT(*) AS ',	//	(S)ELECT (D)ISTINCT (C)OUNT (A)LL (A)S
										'SDAF'			=>	'SELECT DISTINCT COUNT(*) FROM ',//	(S)ELECT (D)ISTINCT (C)OUNT (A)LL (F)ROM

										//	compound statements
										'SAF'			=>	'SELECT *' . PHP_EOL . 'FROM' . PHP_EOL . "\t",
										'SELECT_ALL_FROM'=>	'SELECT *' . PHP_EOL . 'FROM' . PHP_EOL . "\t",
										'SCAF'			=>	'SELECT COUNT(*)' . PHP_EOL . 'FROM' . PHP_EOL . "\t",

										'SC'			=>	'SELECT COUNT(*)',		//	SA = (S)ELECT (C)OUNT (ALL) is implied here
										'SC_AS'			=>	'SELECT COUNT(*) AS ',	//	SA = (S)ELECT (C)OUNT (ALL) is implied here
										'SCA'			=>	'SELECT COUNT(*)',		//	SA = (S)ELECT (C)OUNT (A)LL
										'SCAA'			=>	'SELECT COUNT(*) AS',	//	SA = (S)ELECT (C)OUNT (A)LL (A)S
										'SCA_AS'		=>	'SELECT COUNT(*) AS',	//	SA = (S)ELECT (C)OUNT (A)LL
										'S_COUNT_ALL'	=>	'SELECT COUNT(*)',
										'S_COUNT_ALL_AS'=>	'SELECT COUNT(*) AS ',
										'SELECT_CA'		=>	'SELECT COUNT(*)',		//	CA = (C)OUNT (A)LL = COUNT(*)
										'SELECT_CA_AS'	=>	'SELECT COUNT(*) AS ',
										'SELECT_CALL'	=>	'SELECT COUNT(*)',
										'SELECT_CALL_AS'=>	'SELECT COUNT(*) AS ',
										'SELECT_COUNT_ALL'=>'SELECT COUNT(*)',
										'SELECT_COUNT_ALL_AS'=>'SELECT COUNT(*) AS ',

										'CREATE'		=>	'CREATE ',
										'DROP'			=>	'DROP ',
										'CREATE_TABLE'	=>	'CREATE TABLE ',
										'ALTER'			=>	'ALTER ',
										'ALTER_TABLE'	=>	'ALTER TABLE ',
										'ALTER_DATABASE'=>	'ALTER DATABASE ',		//	https://dev.mysql.com/doc/refman/5.7/en/alter-database.html
										'ALTER_SCHEMA'	=>	'ALTER SCHEMA ',		//	https://dev.mysql.com/doc/refman/5.7/en/alter-database.html
										'ALTER_EVENT'	=>	'ALTER EVENT ',			//	https://dev.mysql.com/doc/refman/5.7/en/alter-event.html
										'ALTER_FUNCTION'=>	'ALTER FUNCTION ',		//	https://dev.mysql.com/doc/refman/5.7/en/alter-function.html
										'DATABASE'		=>	'DATABASE ',			//	https://dev.mysql.com/doc/refman/5.7/en/alter-database.html
										'SCHEMA'		=>	'SCHEMA ',				//	https://dev.mysql.com/doc/refman/5.7/en/alter-database.html
										'EVENT'			=>	'EVENT ',				//	https://dev.mysql.com/doc/refman/5.7/en/alter-event.html
										'FUNCTION'		=>	'FUNCTION ',			//	https://dev.mysql.com/doc/refman/5.7/en/alter-function.html
										'TABLE'			=>	'TABLE ',				//	https://dev.mysql.com/doc/refman/5.7/en/truncate-table.html		TRUNCATE [TABLE] tbl_name || CREATE TABLE || ALTER TABLE

										'ALL'			=>	'*',					//	https://dev.mysql.com/doc/refman/5.7/en/select.html		`The ALL and DISTINCT modifiers specify whether duplicate rows should be returned. ALL (the default) specifies that all matching rows should be returned, including duplicates. DISTINCT specifies removal of duplicate rows from the result set. It is an error to specify both modifiers. DISTINCTROW is a synonym for DISTINCT.`
										'DISTINCT'		=>	'DISTINCT ',			//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SELECT DISTINCT || MIN(DISTINCT price)
										'DISTINCTROW'	=>	'DISTINCTROW ',			//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SELECT DISTINCT || MIN(DISTINCT price)
										'HIGH_PRIORITY'	=>	'HIGH_PRIORITY ',		//	https://dev.mysql.com/doc/refman/5.7/en/select.html		HIGH_PRIORITY gives the SELECT higher priority than a statement that updates a table.
										'HIGH'			=>	'HIGH_PRIORITY ',		//	https://dev.mysql.com/doc/refman/5.7/en/select.html		HIGH_PRIORITY gives the SELECT higher priority than a statement that updates a table.
										'STRAIGHT_JOIN'	=>	'STRAIGHT_JOIN ',		//	https://dev.mysql.com/doc/refman/5.7/en/select.html		`STRAIGHT_JOIN forces the optimizer to join the tables in the order in which they are listed in the FROM clause. You can use this to speed up a query if the optimizer joins the tables in nonoptimal order. STRAIGHT_JOIN also can be used in the table_references list. See Section 13.2.9.2, “JOIN Syntax”.`
										'SQL_SMALL_RESULT'=>'SQL_SMALL_RESULT ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_BIG_RESULT or SQL_SMALL_RESULT can be used with GROUP BY or DISTINCT to tell the optimizer that the result set has many rows or is small, respectively.
										'SMALL'			=>	'SQL_SMALL_RESULT ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_BIG_RESULT or SQL_SMALL_RESULT can be used with GROUP BY or DISTINCT to tell the optimizer that the result set has many rows or is small, respectively.
										'SQL_BIG_RESULT'=>	'SQL_BIG_RESULT ',		//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_BIG_RESULT or SQL_SMALL_RESULT can be used with GROUP BY or DISTINCT to tell the optimizer that the result set has many rows or is small, respectively.
										'BIG'			=>	'SQL_BIG_RESULT ',		//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_BIG_RESULT or SQL_SMALL_RESULT can be used with GROUP BY or DISTINCT to tell the optimizer that the result set has many rows or is small, respectively.
										'SQL_BUFFER_RESULT'=>'SQL_BUFFER_RESULT ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_BUFFER_RESULT forces the result to be put into a temporary table. This helps MySQL free the table locks early and helps in cases where it takes a long time to send the result set to the client. This modifier can be used only for top-level SELECT statements, not for subqueries or following UNION.
										'BUFFER'		=>	'SQL_BUFFER_RESULT ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_BUFFER_RESULT forces the result to be put into a temporary table. This helps MySQL free the table locks early and helps in cases where it takes a long time to send the result set to the client. This modifier can be used only for top-level SELECT statements, not for subqueries or following UNION.
										'SQL_CACHE'		=>	'SQL_CACHE ',			//	https://dev.mysql.com/doc/refman/5.7/en/select.html		The SQL_CACHE and SQL_NO_CACHE modifiers affect caching of query results in the query cache (see Section 8.10.3, “The MySQL Query Cache”). SQL_CACHE tells MySQL to store the result in the query cache if it is cacheable and the value of the query_cache_type system variable is 2 or DEMAND. With SQL_NO_CACHE, the server does not use the query cache. It neither checks the query cache to see whether the result is already cached, nor does it cache the query result.
										'CACHE'			=>	'SQL_CACHE ',			//	https://dev.mysql.com/doc/refman/5.7/en/select.html		The SQL_CACHE and SQL_NO_CACHE modifiers affect caching of query results in the query cache (see Section 8.10.3, “The MySQL Query Cache”). SQL_CACHE tells MySQL to store the result in the query cache if it is cacheable and the value of the query_cache_type system variable is 2 or DEMAND. With SQL_NO_CACHE, the server does not use the query cache. It neither checks the query cache to see whether the result is already cached, nor does it cache the query result.
										'SQL_NO_CACHE'	=>	'SQL_NO_CACHE ',		//	https://dev.mysql.com/doc/refman/5.7/en/select.html		The SQL_CACHE and SQL_NO_CACHE modifiers affect caching of query results in the query cache (see Section 8.10.3, “The MySQL Query Cache”). SQL_CACHE tells MySQL to store the result in the query cache if it is cacheable and the value of the query_cache_type system variable is 2 or DEMAND. With SQL_NO_CACHE, the server does not use the query cache. It neither checks the query cache to see whether the result is already cached, nor does it cache the query result.
										'NO_CACHE'		=>	'SQL_NO_CACHE ',		//	https://dev.mysql.com/doc/refman/5.7/en/select.html		The SQL_CACHE and SQL_NO_CACHE modifiers affect caching of query results in the query cache (see Section 8.10.3, “The MySQL Query Cache”). SQL_CACHE tells MySQL to store the result in the query cache if it is cacheable and the value of the query_cache_type system variable is 2 or DEMAND. With SQL_NO_CACHE, the server does not use the query cache. It neither checks the query cache to see whether the result is already cached, nor does it cache the query result.
										'SQL_CALC_FOUND_ROWS'=>	'SQL_CALC_FOUND_ROWS ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html	SQL_CALC_FOUND_ROWS tells MySQL to calculate how many rows there would be in the result set, disregarding any LIMIT clause. The number of rows can then be retrieved with SELECT FOUND_ROWS(). See Section 12.14, “Information Functions”.
										'CALC'			=>	'SQL_CALC_FOUND_ROWS ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_CALC_FOUND_ROWS tells MySQL to calculate how many rows there would be in the result set, disregarding any LIMIT clause. The number of rows can then be retrieved with SELECT FOUND_ROWS(). See Section 12.14, “Information Functions”.

										'DELAYED'		=>	'DELAYED ',				//	https://dev.mysql.com/doc/refman/5.7/en/insert.html		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name

										'LOW_PRIORITY'	=>	'LOW_PRIORITY ',		//	https://dev.mysql.com/doc/refman/5.7/en/delete.html		DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
										'LOW'			=>	'LOW_PRIORITY ',		//	https://dev.mysql.com/doc/refman/5.7/en/delete.html		DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
										'QUICK'			=>	'QUICK ',				//	https://dev.mysql.com/doc/refman/5.7/en/delete.html		DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
										'IGNORE'		=>	'IGNORE ',				//	https://dev.mysql.com/doc/refman/5.7/en/delete.html		DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name

										'TRUNCATE'		=>	'TRUNCATE ',			//	https://dev.mysql.com/doc/refman/5.7/en/truncate-table.html		TRUNCATE [TABLE] tbl_name
										'TRUNCATE_TABLE'=>	'TRUNCATE TABLE ',		//	https://dev.mysql.com/doc/refman/5.7/en/truncate-table.html		TRUNCATE [TABLE] tbl_name
										'TT'			=>	'TRUNCATE TABLE ',		//	https://dev.mysql.com/doc/refman/5.7/en/truncate-table.html		TRUNCATE [TABLE] tbl_name

										'CA'			=>	'COUNT(*)',
										'CAA'			=>	'COUNT(*) AS ',			//	(C)OUNT (A)LL (A)S
										'CA_AS'			=>	'COUNT(*) AS ',			//	(C)OUNT (A)LL (AS)
										'COUNT_ALL'		=>	'COUNT(*)',
										'COUNT_ALL_AS'	=>	'COUNT(*) AS ',			//	->SELECT->COUNT_ALL_AS->count_of_all->FROM->users
										'COUNT'			=>	'COUNT',
										'LAST_INSERT_ID'=>	'LAST_INSERT_ID()',		//	SELECT LAST_INSERT_ID();	UPDATE sequence SET id=LAST_INSERT_ID(id+1);
										'ROW_COUNT'		=>	'ROW_COUNT()',			//	https://dev.mysql.com/doc/refman/5.7/en/information-functions.html#function_row-count		SELECT ROW_COUNT();
										'A'				=>	'*',					//	`ALL` is a SELECT modifier ... gonna change its meaning!
										'STAR'			=>	'*',

										'FROM'			=>	PHP_EOL . 'FROM'               . PHP_EOL . "\t",
										'JOIN'			=>	PHP_EOL . "\tJOIN"             . PHP_EOL . "\t\t",
										'LEFT_JOIN'		=>	PHP_EOL . "\tLEFT JOIN"        . PHP_EOL . "\t\t",
										'LEFT_OUTER_JOIN'=>	PHP_EOL . "\tLEFT OUTER JOIN"  . PHP_EOL . "\t\t",
										'RIGHT_JOIN'	=>	PHP_EOL . "\tRIGHT JOIN"       . PHP_EOL . "\t\t",
										'RIGHT_OUTER_JOIN'=>PHP_EOL . "\tRIGHT OUTER JOIN" . PHP_EOL . "\t\t",
										'INNER_JOIN'	=>	PHP_EOL . "\tINNER JOIN"       . PHP_EOL . "\t\t",
										'OUTER_JOIN'	=>	PHP_EOL . "\tOUTER JOIN"       . PHP_EOL . "\t\t",
										'CROSS_JOIN'	=>	PHP_EOL . "\tCROSS JOIN"       . PHP_EOL . "\t\t",
										'STRAIGHT_JOIN'	=>	PHP_EOL . "\tSTRAIGHT_JOIN"    . PHP_EOL . "\t\t",
										'NATURAL_JOIN'	=>	PHP_EOL . "\tNATURAL JOIN"     . PHP_EOL . "\t\t",
										'WHERE'			=>	PHP_EOL . 'WHERE'              . PHP_EOL . "\t",
										'GROUP_BY'		=>	PHP_EOL . 'GROUP BY',
										'HAVING'		=>	PHP_EOL . 'HAVING ',
										'ORDER_BY'		=>	PHP_EOL . 'ORDER BY ',
										'LIMIT'			=>	PHP_EOL . 'LIMIT ',
										'PROCEDURE'		=>	PHP_EOL . 'PROCEDURE ',
										'INTO_OUTFILE'	=>	PHP_EOL . 'INTO OUTFILE ',
										'UNION'			=>	PHP_EOL . 'UNION'          . PHP_EOL,
										'UNION_ALL'		=>	PHP_EOL . 'UNION ALL'      . PHP_EOL,
										'UNION_DISTINCT'=>	PHP_EOL . 'UNION DISTINCT' . PHP_EOL,
										'EXCEPT'		=>	PHP_EOL . 'EXCEPT'         . PHP_EOL,
										'VALUES'		=>	PHP_EOL . 'VALUES'         . PHP_EOL . "\t",
										'ADD'			=>	PHP_EOL . 'ADD ',

										'S'				=>	'SELECT ',
										'D'				=>	'DELETE ',
										'DF'			=>	'DELETE FROM ',
										'I'				=>	'INSERT ',
										'II'			=>	'INSERT INTO ',
										'U'				=>	'UPDATE ',
										'F'				=>	PHP_EOL . 'FROM'               . PHP_EOL . "\t",
										'J'				=>	PHP_EOL . "\tJOIN"             . PHP_EOL . "\t\t",	//	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'IJ'			=>	PHP_EOL . "\tINNER JOIN"       . PHP_EOL . "\t\t",	//	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'LJ'			=>	PHP_EOL . "\tLEFT JOIN"        . PHP_EOL . "\t\t",	//	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'LOJ'			=>	PHP_EOL . "\tLEFT OUTER JOIN"  . PHP_EOL . "\t\t",	//	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'RJ'			=>	PHP_EOL . "\tRIGHT JOIN"       . PHP_EOL . "\t\t",	//	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'ROJ'			=>	PHP_EOL . "\tRIGHT OUTER JOIN" . PHP_EOL . "\t\t",	//	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'OJ'			=>	PHP_EOL . "\tOUTER JOIN"       . PHP_EOL . "\t\t",	//	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'CJ'			=>	PHP_EOL . "\tCROSS JOIN"       . PHP_EOL . "\t\t",	//	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'SJ'			=>	PHP_EOL . "\tSTRAIGHT_JOIN"    . PHP_EOL . "\t\t",	//	Why the hell do they use _ in this name?
										'NJ'			=>	PHP_EOL . "\tNATURAL JOIN"     . PHP_EOL . "\t\t",	//	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'W'				=>	PHP_EOL . 'WHERE'              . PHP_EOL . "\t",
										'G'				=>	PHP_EOL . 'GROUP BY ',
										'H'				=>	PHP_EOL . 'HAVING ',
										'O'				=>	PHP_EOL . 'ORDER BY ',
										'OB'			=>	PHP_EOL . 'ORDER BY ',
										'L'				=>	PHP_EOL . 'LIMIT ',

										'USING'			=>	' USING ',							//	Not sure about the spacing on this statement! Just adding on both sides! The statement actually needs brackets so ... USING (id)
										'USE'			=>	' USE ',							//	USE an index ... spacing ??? ...	https://dev.mysql.com/doc/refman/5.7/en/join.html
										'IGNORE'		=>	' IGNORE ',							//	IGNORE an index		spacing?		https://dev.mysql.com/doc/refman/5.7/en/join.html
										'FORCE'			=>	' FORCE ',							//	FORCE an index		spacing?		https://dev.mysql.com/doc/refman/5.7/en/join.html
										'NATURAL'		=>	' NATURAL ',						//	FORCE an index		spacing?		https://dev.mysql.com/doc/refman/5.7/en/join.html

										'DESC'			=>	' DESC',
										'ASC'			=>	' ASC',
										'IN'			=>	'IN',
										'IN_'			=>	'IN ',
										'_IN'			=>	' IN',
										'_IN_'			=>	' IN ',
										'NOT_IN'		=>	'NOT IN',
										'NOT_IN_'		=>	'NOT IN ',
										'_NOT_IN'		=>	' NOT IN',
										'_NOT_IN_'		=>	' NOT IN ',
										'NOT'			=>	'NOT',
										'NOT_'			=>	'NOT ',
										'_NOT'			=>	' NOT',
										'_NOT_'			=>	' NOT ',
										'NULL'			=>	'NULL',
										'NULL_'			=>	'NULL ',
										'_NULL'			=>	' NULL',
										'_NULL_'		=>	' NULL ',
										'IS'			=>	'IS',
										'IS_'			=>	'IS ',
										'_IS'			=>	' IS',
										'_IS_'			=>	' IS ',
										'IS_NOT'		=>	'IS NOT',
										'IS_NOT_'		=>	'IS NOT ',
										'_IS_NOT'		=>	' IS NOT',
										'_IS_NOT_'		=>	' IS NOT ',
										'IS_NULL'		=>	'IS NULL',
										'IS_NULL_'		=>	'IS NULL ',
										'_IS_NULL'		=>	' IS NULL',
										'_IS_NULL_'		=>	' IS NULL ',
										'LIKE'			=>	'LIKE',
										'LIKE_'			=>	'LIKE ',
										'_LIKE'			=>	' LIKE',
										'_LIKE_'		=>	' LIKE ',
										'NOT_LIKE'		=>	'NOT LIKE',
										'NOT_LIKE_'		=>	'NOT LIKE ',
										'_NOT_LIKE'		=>	' NOT LIKE',
										'_NOT_LIKE_'	=>	' NOT LIKE ',
										'CHARACTER_SET'	=>	' CHARACTER SET ',					//	[INTO OUTFFILE 'file_name' [CHARACTER SET charset_name]
										'CHARACTER'		=>	' CHARACTER ',						//	[INTO OUTFFILE 'file_name' [CHARACTER SET charset_name] ... where else is CHARACTER used? Table definitions and?
										'INTO_DUMPFILE'	=>	' INTO DUMPFILE ',					//	[INTO OUTFILE 'file_name' [CHARACTER SET charset_name] export_options | INTO DUMPFILE 'file_name'
										'DUMPFILE'		=>	'DUMPFILE ',						//	[INTO OUTFILE 'file_name' [CHARACTER SET charset_name] export_options | INTO DUMPFILE 'file_name'
										'OUTFILE'		=>	'OUTFILE ',
																								//	INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name
										'INTO'			=>	'INTO ',							//	[INTO OUTFILE 'file_name' [CHARACTER SET charset_name] export_options | INTO DUMPFILE 'file_name' | INTO var_name [, var_name]]
										'OFFSET'		=>	' OFFSET ',							//	[LIMIT {[offset,] row_count | row_count OFFSET offset}]

										//	These can only come at the end of a SELECT, not sure if they can be used in other statements?
										'FOR_UPDATE'					=>	PHP_EOL . 'FOR UPDATE',							//	[FOR UPDATE | LOCK IN SHARE MODE]]
										'LOCK_IN_SHARE_MODE'			=>	' LOCK IN SHARE MODE',							//	[FOR UPDATE | LOCK IN SHARE MODE]]
										'FOR_UPDATE_LOCK_IN_SHARE_MODE'	=>	PHP_EOL . 'FOR UPDATE LOCK IN SHARE MODE',		//	[FOR UPDATE | LOCK IN SHARE MODE]]

										'ON_DUPLICATE_KEY_UPDATE'		=>	PHP_EOL . 'ON DUPLICATE KEY UPDATE' . PHP_EOL . "\t",	//	https://dev.mysql.com/doc/refman/5.7/en/insert.html

										'AUTO_INCREMENT'=>	' AUTO_INCREMENT',					//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'INT'			=>	' INT',								//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'PK'			=>	'PRIMARY KEY ',						//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'PRIMARY_KEY'	=>	'PRIMARY KEY ',						//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'UNIQUE_KEY'	=>	'UNIQUE KEY ',						//	CREATE TABLE `t` `id` INT(11) NOT NULL AUTO_INCREMENT, `val` INT(11) DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `i1` (`val`)
									//	'PRIMARY'		=>	'PRIMARY ',							//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))	//	needs work ...
									//	'KEY'			=>	'KEY ',								//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'ENGINE'		=>	PHP_EOL . 'ENGINE',					//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b)) ENGINE=MyISAM SELECT b,c FROM test2;

										'IF'			=>	' IF ',
										'SET'			=>	' SET ',							//	There is also `CHARACTER SET charset_name` ... so double spaces if you use CHARACTER->SET->...

										'COMMA'			=>	', ',
										'C'				=>	', ',								//	COMMA (or COUNT or CLOSE)  ??? ... O is for ORDER BY ... so we can't really use C for CLOSE, and `COUNT` is usually used with COUNT(*)
									//	'C'				=>	', ',
									//	'C'				=>	')',
										'c_'			=>	', ',	//	currently the only lower case, case ... how else do we get a comma???

										'_'				=>	' ',	//	SPACE
										'__'			=>	', ',	//	COMMA
									//	'___'			=>	'(' / ')',	// more than 2 underscores has special meaning
										'Q'				=>	'"',
										'SPACE'			=>	' ',
										'SP'			=>	' ',	//	SPACE (also Stored Procedure)
										'_O'			=>	'(',	//	OP	? || O
										'C_'			=>	')',	//	CL	? || C
										'OPEN'			=>	'(',
										'CLOSE'			=>	')',
										'TAB'			=>	"\t",
										'NL'			=>	"\n",
										'CR'			=>	"\r",
										'EOL'			=>	PHP_EOL,
										'BR'			=>	PHP_EOL,
										'EQ'			=>	'=',
										'EQ_'			=>	'= ',
										'_EQ'			=>	' =',
										'_EQ_'			=>	' = ',
										'NEQ'			=>	'!=',
										'NEQ_'			=>	'!= ',
										'_NEQ'			=>	' !=',
										'_NEQ_'			=>	' != ',
										'NOTEQ'			=>	'!=',
										'NOTEQ_'		=>	'!= ',
										'_NOTEQ'		=>	' !=',
										'_NOTEQ_'		=>	' != ',
										'NOT_EQ'		=>	'!=',
										'NOT_EQ_'		=>	'!= ',
										'_NOT_EQ'		=>	' !=',
										'_NOT_EQ_'		=>	' != ',
										'GT'			=>	'>',
										'GT_'			=>	'> ',
										'_GT'			=>	' >',
										'_GT_'			=>	' > ',
										'GE'			=>	'>=',
										'GE_'			=>	'>= ',
										'_GE'			=>	' >=',
										'_GE_'			=>	' >= ',
										'GTEQ'			=>	'>=',
										'GTEQ_'			=>	'>= ',
										'_GTEQ'			=>	' >=',
										'_GTEQ_'		=>	' >= ',
										'LT'			=>	'<',
										'LT_'			=>	'< ',
										'_LT'			=>	' <',
										'_LT_'			=>	' < ',
										'LE'			=>	'<=',
										'LE_'			=>	'<= ',
										'_LE'			=>	' <=',
										'_LE_'			=>	' <= ',
										'LTEQ'			=>	'<=',
										'LTEQ_'			=>	'<= ',
										'_LTEQ'			=>	' <=',
										'_LTEQ_'		=>	' <= ',
										'AS'			=>	'AS',
										'AS_'			=>	'AS ',
										'_AS'			=>	' AS',
										'_AS_'			=>	' AS ',
										'ON'			=>	'ON',
										'ON_'			=>	'ON ',
										'_ON'			=>	' ON',
										'_ON_'			=>	' ON ',
										'AND'			=>	'AND',
										'AND_'			=>	'AND ',
										'_AND'			=>	' AND',
										'_AND_'			=>	' AND ',
										'OR'			=>	'OR',		//	what about logical/bitwise | (or) ???
										'OR_'			=>	'OR ',
										'_OR'			=>	' OR',
										'_OR_'			=>	' OR ',
										'XOR'			=>	'XOR',
										'XOR_'			=>	'XOR ',
										'_XOR'			=>	' XOR',
										'_XOR_'			=>	' XOR ',
										'ADD'			=>	'+',
										'ADD_'			=>	'+ ',
										'_ADD'			=>	' +',
										'_ADD_'			=>	' + ',
										'SUB'			=>	'-',		//	NEG / SUB ... same operator
										'SUB_'			=>	'- ',
										'_SUB'			=>	' -',
										'_SUB_'			=>	' - ',
										'NEG'			=>	'-',
										'NEG_'			=>	'- ',
										'_NEG'			=>	' -',
										'_NEG_'			=>	' - ',
										'MUL'			=>	'*',
										'MUL_'			=>	'* ',
										'_MUL'			=>	' *',
										'_MUL_'			=>	' * ',
										'DIV'			=>	'/',
										'DIV_'			=>	'/ ',
										'_DIV'			=>	' /',
										'_DIV_'			=>	' / ',
										'MOD'			=>	'%',
										'MOD_'			=>	'% ',
										'_MOD'			=>	' %',
										'_MOD_'			=>	' % ',

										'MATCH'			=>	'MATCH',	//	MySQL syntax: MATCH (x) AGAINST (y IN BOOLEAN MODE)
										'MATCH_'		=>	'MATCH ',	//	PostgreSQL: x @@ to_tsquery(y)
										'_MATCH'		=>	' MATCH',	//	Oracle: CONTAINS(x, y)
										'_MATCH_'		=>	' MATCH ',	//	SQLlite: MATCH

										'AFTER'			=>	'AFTER',
										'AFTER_'		=>	'AFTER ',
										'_AFTER'		=>	' AFTER',
										'_AFTER_'		=>	' AFTER ',

										/**
										 *	Numeric replacements, they currently exclude spacing,
										 *		because _EQ_ / _AND_ can provide the spacing if necessary!
										 *	The `_` prefix is required because these are property names,
										 *		and properties cannot start with a number!
										 *
										 *	eg. SQL()->SELECT_ALL_FROM->users->WHERE->id->_EQ_->_0->_OR_->_10;
										 *		SQL()->SELECT_ALL_FROM->users->WHERE->id->BETWEEN->_0_->_AND_->_100;
										 */
										'_0_'			=>	'0',	'_0'			=>	'0',
										'_1_'			=>	'1',	'_1'			=>	'1',
										'_2_'			=>	'2',	'_2'			=>	'2',
										'_3_'			=>	'3',	'_3'			=>	'3',
										'_4_'			=>	'4',	'_4'			=>	'4',
										'_5_'			=>	'5',	'_5'			=>	'5',
										'_6_'			=>	'6',	'_6'			=>	'6',
										'_7_'			=>	'7',	'_7'			=>	'7',
										'_8_'			=>	'8',	'_8'			=>	'8',
										'_9_'			=>	'9',	'_9'			=>	'9',
										'_10_'			=>	'10',	'_10'			=>	'10',
										'_11_'			=>	'11',	'_11'			=>	'11',
										'_12_'			=>	'12',	'_12'			=>	'12',
										'_13_'			=>	'13',	'_13'			=>	'13',
										'_14_'			=>	'14',	'_14'			=>	'14',
										'_15_'			=>	'15',	'_15'			=>	'15',
										'_16_'			=>	'16',	'_16'			=>	'16',
										'_17_'			=>	'17',	'_17'			=>	'17',
										'_18_'			=>	'18',	'_18'			=>	'18',
										'_19_'			=>	'19',	'_19'			=>	'19',
										'_20_'			=>	'20',	'_20'			=>	'20',
										'_21_'			=>	'21',	'_21'			=>	'21',
										'_22_'			=>	'22',	'_22'			=>	'22',
										'_23_'			=>	'23',	'_23'			=>	'23',
										'_24_'			=>	'24',	'_24'			=>	'24',
										'_25_'			=>	'25',	'_25'			=>	'25',
										'_26_'			=>	'26',	'_26'			=>	'26',
										'_27_'			=>	'27',	'_27'			=>	'27',
										'_28_'			=>	'28',	'_28'			=>	'28',
										'_29_'			=>	'29',	'_29'			=>	'29',

										'_30_'			=>	'30', '_35_' => '35', '_40_' => '40', '_45_' => '45', '_50_' => '50',
										'_55_'			=>	'55', '_60_' => '60', '_65_' => '65', '_70_' => '70', '_75_' => '75',
										'_80_'			=>	'80', '_85_' => '85', '_90_' => '90', '_95_' => '95', '_100_' => '100',

										'_30'			=>	'30', '_35_' => '35', '_40_' => '40', '_45_' => '45', '_50_' => '50',
										'_55'			=>	'55', '_60_' => '60', '_65_' => '65', '_70_' => '70', '_75_' => '75',
										'_80'			=>	'80', '_85_' => '85', '_90_' => '90', '_95_' => '95', '_100_' => '100',

										'BETWEEN'		=>	' BETWEEN ',
										'_BETWEEN_'		=>	' BETWEEN ',						//	might look better in queries?

										'OUT'			=>	'OUT ',								//	https://dev.mysql.com/doc/refman/5.7/en/call.html		CREATE PROCEDURE p (OUT ver_param VARCHAR(25), INOUT incr_param INT)
										'_OUT_'			=>	' OUT ',							//	https://dev.mysql.com/doc/refman/5.7/en/call.html		CREATE PROCEDURE p (OUT ver_param VARCHAR(25), INOUT incr_param INT)
										'INOUT'			=>	'INOUT ',							//	https://dev.mysql.com/doc/refman/5.7/en/call.html		CREATE PROCEDURE p (OUT ver_param VARCHAR(25), INOUT incr_param INT)
										'_INOUT_'		=>	' INOUT ',							//	https://dev.mysql.com/doc/refman/5.7/en/call.html		CREATE PROCEDURE p (OUT ver_param VARCHAR(25), INOUT incr_param INT)

																								//	INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)]
										'PARTITION'		=>	PHP_EOL . 'PARTITION ',				//	https://dev.mysql.com/doc/refman/5.7/en/select.html		[FROM table_references [PARTITION partition_list]
										'WITH_ROLLUP'	=>	' WITH ROLLUP ',					//	https://dev.mysql.com/doc/refman/5.7/en/select.html		[GROUP BY {col_name | expr | position} [ASC | DESC], ... [WITH ROLLUP]]
										'DEFAULT'		=>	' DEFAULT ',
									];


	/**
	 *	->SELECT_ALL->FROM
	 *
	 *
	 *
	 */


	/**
	 *	->SELECT('*')
	 *	or
	 *	->SELECT() 				<<== reset $comma because nothing was supplied!
	 *		->('*')				<<== now set $comma to ', '
	 *		or
	 *		->COUNT()			<<== now set $comma to ', '
	 *		->MIN('price')		<<== now set $comma to ', '
	 *		->('prices')		<<== $sql .= ', prices'; ... how to handle this ??? ... maybe I can just detect if a comma is first used when we are in SELECT context ... otherwise we should just join without modification!
	 *		->(', prices')		<<== $sql .= ', prices'; ... I think I should use () to DIRECTLY add text WITHOUT modification!
	 *		->SELECT('(SELECT * FROM users u WHERE u.id = p.user_id) AS OMG')
	 *	->FROM('table t')						<<== now reset $comma for future statements
	 *		->WHERE()							<<== reset $comma again
	 *		->('joker = 123') 					<<== how to handle this ???
	 *		->('username = ?', $user) 			<<== how to handle this ???
	 *		->('username LIKE ?%', $user) 		<<== ... we need to detect if ?% or %? or %?%
	 *		->('username LIKE "?%"', $user)		<<== how to handle this ???
	 *		->LIKE('?%', $user)
	 *		->AND()
	 *
	 *
	 *
	 *
	 */

	//	eg. $sql = SQL()->SELECT('*')		<<== context here ...
	//			->COUNT()
	//			->MIN('price')
	//		->EXPLAIN();


    /**
     * Use `$container = new Container()` if you want a container with the default configuration.
     *
     * If you want to customize the container's behavior, you are discouraged to create and pass the
     * dependencies yourself, the ContainerBuilder class is here to help you instead.
     *
     * @see ContainerBuilder
     *
     * @param ContainerInterface $wrapperContainer If the container is wrapped by another container.
     */
//    public function __construct(
	/**
	 *	Creates new statement with the powerful `prepare()` syntax
	 *
	 *	@param string $stmt Statement in `prepare()` syntax, all `?`, `@` and `%` values must be escaped!
	 *	@param mixed ...$params Parameters to use
	 *	@return $this
	 */
	public function __construct(string $stmt = null, ...$params)
	{
		/*
		if (self::$conn === null) {
			$this->sql =	'** USING DUMMY CONNECTION FOR TESTING ONLY ** ' . PHP_EOL .
							'** please call SQL::setConn() with a valid MySQLi connection when you are ready! ** ' . PHP_EOL . PHP_EOL;
			self::setDummyConn();
		}
		*/

		if (empty($params)) {
			$this->sql = $stmt;
		}
		else {
			$this->prepare($stmt, ...$params);
		}
	}

	public function __toString()
	{
		return $this->sql;
	}

	/**
	 *
	 */
	public function EXPLAIN(string $stmt = null, ...$params)
	{
		if ($stmt === null) {
			$this->sql = 'EXPLAIN ' . $this->sql;
			return $this;
		}
		return $this->prepare('EXPLAIN ' . $stmt, ...$params);
	}

	/**
	 *	CALL MySQL/PDO Stored Procudure
	 *
	 *	TODO: Detect the connection type; and use the appropriate syntax; because PostgreSQL uses `SELECT sp_name(...)`
	 *
	 *	To disable value escaping, use one of the following techniques:
	 *			->CALL('sp_name(LAST_INSERT_ID(), @, @, ?:varchar:4000)', 'u.name', '@sql_variable', $name)
	 *			->CALL('sp_name', ['@' => 'LAST_INSERT_ID()'])
	 *			->CALL('sp_name(@, ?)', 'LAST_INSERT_ID()', $name)
	 *			->CALL('SELECT sp_name(@, ?)', 'LAST_INSERT_ID()', $name)
	 *			->CALL('SELECT sp_name(LAST_INSERT_ID(), ?)', $name)
	 *
	 *	Examples:
	 *		INTO('users', 'col1', 'col2', 'col3')
	 *		INTO('users', ['col1', 'col2', 'col3'])
	 *		INTO('users', ['col1' => 'value1', 'col2' => 'value2', 'col3' => 'value3'])
	 *		INTO('users', ['col1', 'col2', 'col3'], ['value1', 'value2', 'value3'])
	 *
	 *	Docs:
	 *		MySQL:
	 *			https://dev.mysql.com/doc/refman/5.7/en/call.html
	 *		PostgreSQL:
	 *			https://www.postgresql.org/docs/9.1/static/sql-syntax-calling-funcs.html
	 *		PDO:
	 *			http://php.net/manual/en/pdo.prepared-statements.php
	 *
	 *	SQL Syntax:
	 *		MySQL:
	 *			CALL sp_name([parameter[,...]])
	 *			CALL sp_name[()]
	 *		PostgreSQL:
	 *			SELECT insert_user_ax_register(...);
	 *		PDO:
	 *			$stmt = $pdo->prepare("CALL sp_returns_string(?)");
	 *			$stmt->bindParam(1, $return_value, PDO::PARAM_STR, 4000); 
	 *			$stmt->execute();
	 *
	 *	@param string $tbl_name Table name to `INSERT INTO`
	 *	@param array|string $partitions can be array or string
	 *	@param mixed ... $args Parameters to use, either columns only or column-value pairs
	 *	@return $this
	 */
	public function CALL(string $sp_name = null, ...$params)
	{
		if (strpos($sp_name, '(') === false) {	//	detect if user prepared the format/pattern eg. CALL('sp_name(?, ?, @)', ...)
			return $this->prepare('CALL ' . $sp_name, ...$params);
		}
		return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
	}
	public function C(string $sp_name = null, ...$params)
	{
		if (strpos($sp_name, '(') === false) {
			return $this->prepare('CALL ' . $sp_name, ...$params);
		}
		return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
	}
	public function SP(string $sp_name = null, ...$params)
	{
		if (strpos($sp_name, '(') === false) {
			return $this->prepare('CALL ' . $sp_name, ...$params);
		}
		return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
	}
	public function storedProc(string $sp_name = null, ...$params)		//	WARNING: This version might be different in future, because PostgreSQL uses `SELECT $sp_name` ... I might do an `auto-detect` in this version
	{
		if (strpos($sp_name, '(') === false) {
			return $this->prepare('CALL ' . $sp_name, ...$params);
		}
		return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
	}


	/**
	 *
	 *
	 *
	 *	@see \Sql::prepare()
	 *
	 *
	 *	@param
	 *
	 *
	 *	@return $this
	 */
	public function select(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT ' . $stmt, ...$params);
	}

	/**
	 *	@see \Sql::select()
	 *
	 *	@alias SELECT()
	 */
	public function s(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT ' . $stmt, ...$params);
	}


	public function selectAllFrom(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['SELECT_ALL_FROM'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['SELECT_ALL_FROM'] . $stmt, ...$params);
	}
	public function select_all_from(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['SELECT_ALL_FROM'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['SELECT_ALL_FROM'] . $stmt, ...$params);
	}
	public function SAF(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['SELECT_ALL_FROM'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['SELECT_ALL_FROM'] . $stmt, ...$params);
	}


	public function SELECT_CACHE(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_CACHE ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_CACHE ' . $stmt, ...$params);
	}
	public function selectCache(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_CACHE ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_CACHE ' . $stmt, ...$params);
	}
	public function SC(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_CACHE ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_CACHE ' . $stmt, ...$params);
	}


	public function SELECT_NO_CACHE(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_NO_CACHE ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_NO_CACHE ' . $stmt, ...$params);
	}
	public function selectNoCache(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_NO_CACHE ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_NO_CACHE ' . $stmt, ...$params);
	}
	public function SNC(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_NO_CACHE ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_NO_CACHE ' . $stmt, ...$params);
	}



	/**
	 *	
	 *	
	 *	Example:
	 *		.SELECT_DISTINCT('c1, c2, c3')
	 *		.SELECT_DISTINCT('c1', 'c2', 'c3')
	 *
	 *	Samples:
	 *		SELECT DISTINCT c1, c2, c3 FROM t1 WHERE c1 > const;
	 */
	public function SELECT_DISTINCT(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT DISTINCT ' . $stmt, ...$params);
	}
	public function selectDistinct(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT DISTINCT ' . $stmt, ...$params);
	}
	public function SD(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT DISTINCT ' . $stmt, ...$params);
	}

	public function DISTINCT(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT DISTINCT ' . $stmt, ...$params);
	}

	public function SELECT_CACHE_DISTINCT(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_CACHE DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_CACHE DISTINCT ' . $stmt, ...$params);
	}
	public function selectCacheDistinct(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_CACHE DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_CACHE DISTINCT ' . $stmt, ...$params);
	}
	public function SCD(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_CACHE DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_CACHE DISTINCT ' . $stmt, ...$params);
	}

	public function SELECT_NO_CACHE_DISTINCT(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_NO_CACHE DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_NO_CACHE DISTINCT ' . $stmt, ...$params);
	}
	public function selectNoCacheDistinct(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_NO_CACHE DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_NO_CACHE DISTINCT ' . $stmt, ...$params);
	}
	public function SNCD(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'SELECT SQL_NO_CACHE DISTINCT ' . $stmt;
			return $this;
		}
		return $this->prepare('SELECT SQL_NO_CACHE DISTINCT ' . $stmt, ...$params);
	}



	/**
	 *	Samples:
	 *		UPDATE sequence SET c1 = 123, id = LAST_INSERT_ID(id+1);
	 *		SELECT LAST_INSERT_ID();
	 *
	 *	PROBLEM: If we use `comma` with `UPDATE sequence SET c1 = 123, id = LAST_INSERT_ID(id+1);`  ... c1 will set the comma, but `LAST_INSERT_ID() does NOT require it!
	 */
	public function LAST_INSERT_ID($id = null)
	{
		$this->sql .= (substr($this->sql, -1) === ' ' ? null : ' ') . 'LAST_INSERT_ID(' . $id . ')';
		return $this;
	}
	public function lastInsertId($id = null)
	{
		$this->sql .= (substr($this->sql, -1) === ' ' ? null : ' ') . 'LAST_INSERT_ID(' . $id . ')';
		return $this;
	}


	/**
	 *	Samples:
	 *		->OP('user *', $value)
	 *		UPDATE sequence SET c1 = 123, id = LAST_INSERT_ID(id+1);
	 *		SELECT LAST_INSERT_ID();
	 *
	 *	PROBLEM: If we use `comma` with `UPDATE sequence SET c1 = 123, id = LAST_INSERT_ID(id+1);`  ... c1 will set the comma, but `LAST_INSERT_ID() does NOT require it!
	 */
	public function OP($op, $value = null)
	{
		$this->sql .= (substr($this->sql, -1) === ' ' ? null : ' ') . $op . ' ';
		if (is_numeric($value)) {
			$this->sql .= $value;
		} else if (is_string($value)) {
			$this->sql .= self::quote($value);
		}
		return $this;
	}
	public function SPACE($value = null)
	{
		$this->sql .= (substr($this->sql, -1) === ' ' ? null : ' ') . $value;
		return $this;
	}


	/**
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
	 *
	 *	eg. INSERT('IGNORE')->INTO(...)
	 *
	 */
	public function INSERT(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'INSERT ' . $stmt;
			return $this;
		}
		return $this->prepare('INSERT ' . $stmt, ...$params);
	}
	public function I(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'INSERT ' . $stmt;
			return $this;
		}
		return $this->prepare('INSERT ' . $stmt, ...$params);
	}

	/**
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
	 *
	 *
	 */
	public function INSERT_INTO($tbl_name, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'INSERT INTO ' . $tbl_name;
			return $this;
		}
		$this->sql .= 'INSERT ';
		return $this->INTO($tbl_name, ...$params);
	}
	public function insertInto($tbl_name, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'INSERT INTO ' . $tbl_name;
			return $this;
		}
		$this->sql .= 'INSERT ';
		return $this->INTO($tbl_name, ...$params);
	}
	public function II($tbl_name, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'INSERT INTO ' . $tbl_name;
			return $this;
		}
		$this->sql .= 'INSERT ';
		return $this->INTO($tbl_name, ...$params);
	}

	/**
	 *	detect first character of column title ... if the title has '@' sign, then DO NOT ESCAPE! ... can be useful for 'DEFAULT', 'UNIX_TIMESTAMP()', or '@id' or 'MD5(...)' etc. (a connection variable) etc.
	 *
	 *	Examples:
	 *		INTO('users (col1, col2, dated) VALUES (?, ?, @)', $value1, $value2, 'CURDATE()')	//	VERY useful!
	 *		INTO('users', ['col1', 'col2', '@dated'])											//	not very useful! Just puts the column names in; `@` is stripped from column titles!
	 *		INTO('users', ['col1' => 'value1', 'col2' => 'value2', '@dated' => 'CURDATE()'])	//	column names and values can be nicely formatted on multiple lines
	 *		INTO('users', ['col1', 'col2', '@dated'], ['value1', 'value2', 'CURDATE()'])		//	convenient style if your values are already in an array
	 *		INTO('users', ['col1', 'col2', '@dated'], $value1, $value2, 'CURDATE()')			//	nice ... `dated` column will NOT be escaped!
	 *
	 *
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
	 *
	 *	@param string $stmt Table name or `prepare` style statement
	 *	@param mixed ... $params Parameters to use, either columns only or column-value pairs
	 *	@return $this
	 */
	public function INTO(string $tbl_name = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'INTO ' . $tbl_name;
			return $this;
		}
		if (is_array($params[0]))
		{
			if (count($params) === 1)
			{
				$params = $params[0];
				//	detect the data type of the key for the first value,
				//		if the key is a string, then we have 'col' => 'values' pairs
				if (is_string(key($params)))
				{
					$cols	=	null;
					$values	=	null;
					foreach ($params as $col => $value)
					{
						if ($col[0] === '@') {
							$cols[]		=	substr($col, 1);
							$values[]	=	$value;
						}
						else if (is_numeric($value)) {
							$cols[]		=	$col;
							$values[]	=	$value;
						}
						else if (is_string($value)) {
							$cols[]		=	$col;
							$values[]	=	self::quote($value);
						}
						else if ($value === null) {
							$cols[]		=	$col;
							$values[]	=	'NULL';
						}
						else {
							throw new \BadMethodCallException('Invalid type `' . gettype($value) .
								'` sent to SQL()->INTO("' . $tbl_name . '", ...) statement; only numeric, string and null values are supported!');
						}
					}
					$params = $cols;
				}
				else {
					foreach ($params as $index => $col) {
						if ($col[0] === '@') {	//	strip '@' from beginning of all column names ... just in-case!
							$params[$index] = substr($col, 1);
						}
					}
				}
			}
			else if (is_array($params[1]))
			{
				if (count($params) !== 2) {
					throw new \Exception('When the first two parameters supplied to SQL()->INTO("' . $tbl_name .
							'", ...) statements are arrays, no other parameters are necessary!');
				}
				$cols	=	$params[0];
				$values	=	$params[1];
				if (count($cols) !== count($values)) {
					throw new \Exception('Mismatching number of columns and values: count of $columns array = ' .
							count($cols) . ' and count of $values array = ' . count($values) .
							' (' . count($cols) . ' vs ' . count($values) . ') supplied to SQL()->INTO("' . $tbl_name . '", ...) statement');
				}
				foreach ($cols as $index => $col)
				{
					if ($col[0] === '@') {
						$cols[$index]	=	substr($col, 1);
					//	$values[$index]	=	$value[$index];		//	unchanged
					}
					else {
						$value = $values[$index];
						if (is_numeric($value)) {
						//	$cols[$index]	=	$col;			//	unchanged
						//	$values[$index]	=	$value[$index];	//	unchanged
						}
						else if (is_string($value)) {
						//	$cols[$index]	=	$col;			//	unchanged
							$values[$index]	=	self::quote($value);
						}
						else if ($value === null) {
						//	$cols[$index]	=	$col;			//	unchanged
							$values[$index]	=	'NULL';
						}
						else {
							throw new \Exception('Invalid type `' . gettype($value) .
								'` sent to SQL()->INTO("' . $tbl_name . '", ...) statement; only numeric, string and null values are supported!');
						}
					}
				}
				$params = $cols;
			}
			else
			{	//	syntax: INTO('users', ['col1', 'col2', '@dated'], $value1, $value2, 'CURDATE()')
				$cols	=	array_shift($params);	//	`Shift an element off the beginning of array`
				$values	=	$params;
				if (count($cols) !== count($values)) {
					throw new \Exception('Mismatching number of columns and values: count of $columns array = ' .
							count($cols) . ' and count of $values = ' . count($values) .
							' (' . count($cols) . ' vs ' . count($values) . ') supplied to SQL()->INTO("' . $tbl_name . '", ...) statement');

				}
				foreach ($cols as $index => $col)
				{
					if ($col[0] === '@') {
						$cols[$index]	=	substr($col, 1);
					//	$values[$index]	=	$value[$index];		//	unchanged
					}
					else {
						$value = $values[$index];
						if (is_numeric($value)) {
						//	$cols[$index]	=	$col;			//	unchanged
						//	$values[$index]	=	$value[$index];	//	unchanged
						}
						else if (is_string($value)) {
						//	$cols[$index]	=	$col;			//	unchanged
							$values[$index]	=	self::quote($value);
						}
						else if ($value === null) {
						//	$cols[$index]	=	$col;			//	unchanged
							$values[$index]	=	'NULL';
						}
						else {
							throw new \Exception('Invalid type `' . gettype($value) .
								'` sent to SQL()->INTO("' . $tbl_name . '", ...) statement; only numeric, string and null values are supported!');
						}
					}
				}
				$params = $cols;
			}
			/*
			else
			{
				if (count($params) > 2) {
					throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
						') supplied to SQL()->INTO() statement, when the first parameter is an array,
						you can only supply One or Two arrays as params; One array with column name-value pairs
						or Two arrays with column and values in each.');
				}
				throw new \BadMethodCallException('Invalid parameters (' . count($params) .
					') supplied to SQL()->INTO() statement. Please check the number of `?` and `@` values in the pattern; possibly requiring ' .
					(	substr_count($pattern, '?') + substr_count($pattern, '@') -
						substr_count($pattern, '??') - substr_count($pattern, '@@') -
						substr_count($pattern, '\\?') - substr_count($pattern, '\\@') -
					count($params)) . ' more value(s)');
			}
			*/
		//	$this->sql .= 'INTO ' . $tbl_name .
		//					( ! empty($params)	?	' (' . implode(', ', $params) . ')' : null) .
		//					( ! empty($values)	?	' VALUES (' . implode(', ', $values) . ')' : null);
			$this->sql .= 'INTO ' . $tbl_name . ' (' . implode(', ', $params) . ') ' . (isset($values) ? 'VALUES (' . implode(', ', $values) . ')' : null);
			return $this;
		}
		//	syntax: ->INTO('users (col1, col2, dated) VALUES (?, ?, @)', $value1, $value2, 'CURDATE()')
		return $this->prepare('INTO ' . $tbl_name, ...$params);
	}

	/**
	 *	detect first character of column title ... if the title has '@' sign, then DO NOT ESCAPE! ... can be useful for 'DEFAULT', 'UNIX_TIMESTAMP()', or '@id' or 'MD5(...)' etc. (a connection variable) etc.
	 *
	 *	Examples:
	 *		INTO_PARTITION('users', 'col1', 'col2', 'col3')
	 *		INTO_PARTITION('users', ['col1', 'col2', 'col3'])
	 *		INTO_PARTITION('users', ['col1' => 'value1', 'col2' => 'value2', 'col3' => 'value3'])
	 *		INTO_PARTITION('users', ['col1', 'col2', 'col3'], ['value1', 'value2', 'value3'])
	 *
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
	 *
	 *	@param string $tbl_name Table name to `INSERT INTO`
	 *	@param array|string $partitions can be array or string
	 *	@param mixed ... $args Parameters to use, either columns only or column-value pairs
	 *	@return $this
	 */
	public function INTO_PARTITION(string $tbl_name, $partitions, ...$args)
	{
		if (count($args) === 1 && is_array($args[0]))
		{
			$args = $args[0];
			//	detect the data type of the first key,
			//		if it's a string, then we have 'col' => 'values' pairs
			if (is_string(key($args)))
			{
				$cols	=	null;
				$values	=	null;
				foreach ($args as $col => $value)
				{
					if ($col[0] === '@') {
						$cols[]		=	substr($col, 1);
						$values[]	=	$value;
					}
					else if (is_numeric($value)) {
						$cols[]		=	$col;
						$values[]	=	$value;
					}
					else if ($value === null) {
						$cols[]		=	$col;
						$values[]	=	'NULL';
					}
					else if (is_string($value)) {
						$cols[]		=	$col;
						$values[]	=	$this->returnEscaped($value);
					}
					else {
						throw new \Exception('Invalid type `' . gettype($value) . '` sent to ' . __METHOD__ . '(); only numeric, string and null values are supported!');
					}
				}
				$args = $cols;
			}
			else {
				foreach ($args as $col) {
					if ($col[0] === '@') {	//	strip '@' from beginning of all columns
						$args[key($args)] = substr($col, 1);
					}
				}
			}
		}
		else if (count($args) === 2 && is_array($args[0]))
		{
			if ( ! is_array($args[1])) {
				throw new \Exception('Both first and second parameter of ' . __METHOD__ . ' must be arrays; type: ' . gettype($args[1]) . ' given for the second argument');
			}
			else if (count($args[0]) !== count($args[1])) {
				throw new \Exception('Mismatching count of columns and values: count($columns) = ' . count($args[0]) . ' && count($values) = ' . count($args[1]));
			}
			$cols	=	$args[0];
			$values	=	$args[1];
			foreach ($cols as $index => $col)
			{
				if ($col[0] === '@') {
					$cols[$index]	=	substr($col, 1);
				//	$values[$index]	=	$value[$index];		//	unchanged
				}
				else {
					$value = $values[$index];
					if (is_numeric($value)) {
					//	$cols[$index]	=	$col;			//	unchanged
					//	$values[$index]	=	$value[$index];	//	unchanged
					}
					else if ($value === null) {
					//	$cols[$index]	=	$col;			//	unchanged
						$values[$index]	=	'NULL';
					}
					else if (is_string($value)) {
					//	$cols[$index]	=	$col;			//	unchanged
						$values[$index]	=	$this->returnEscaped($value);
					}
					else {
						throw new \Exception('Invalid type `' . gettype($value) . '` sent to ' . __METHOD__ . '(); only numeric, string and null values are supported!');
					}
				}
			}
			$args = $cols;
		}
		$this->sql .= 'INTO ' . $tbl_name .
						' PARTITION (' . (is_array($partitions) ? implode(', ', $partitions) : $partitions) . ')' .
						( ! empty($args)	?	' (' . implode(', ', $args) . ')' : null) .
						( ! empty($values)	?	' VALUES (' . implode(', ', $values) . ')' : null);
		return $this;
	}
	public function intoPartition(string $tbl_name, $partitions, ...$args)
	{
		return $this->INTO_PARTITION($tbl_name, $partitions, ...$args);
	}

	/**
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
	 *
	 *
	 */
	public function PARTITION(...$args)
	{
		$this->sql .= ' PARTITION (' . implode(', ', $args) . ')';
		return $this;
	}


	/**
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
	 *
	 *
	 *	ANY $key/$index value starting with '@' will cause the value to NOT be escaped!
	 *	eg. VALUES(['value1', '@' => 'UNIX_TIMESTAMP()', '@1' => 'MAX(table)', '@2' => 'DEFAULT', '@3' => 'NULL'])
	 *	eg. VALUES('?, @, @', 'value1', 'DEFAULT', 'NULL')
	 *	eg. VALUES('5, 6, 7, 8, @id, CURDATE()')
	 */
	public function VALUES($stmt = null, ...$params)
	{
		if (empty($params))
		{
			if (is_array($stmt)) {
				$values = '';
				$comma = null;
				foreach ($stmt as $col => $value) {
					if (is_numeric($value)) {
						$values .= $comma . $value;
					}
					else if (is_string($value)) {
						if (is_string($col) && $col[0] === '@') {	//	detect `raw output` modifier in column key/index/name!
							$values .= $comma . $value;
						}
						else {
							$values .= $comma . self::quote($value);
						}
					}
					else if ($value === null) {
						$values .= $comma . 'NULL';
					}
					else {
						throw new \Exception('Invalid type `' . gettype($value) .
							'` sent to VALUES([..]); only numeric, string and null are supported!');
					}
					$comma = ', ';
				}
			}
			else {
				$values = $stmt;
			}
			$this->sql .= ' VALUES (' . $values . ')';
			return $this;
		}
		return $this->prepare(' VALUES (' . $stmt . ')', ...$params);
	}
	public function V($stmt = null, ...$params)
	{
		if (empty($params))
		{
			if (is_array($stmt)) {
				$values = '';
				$comma = null;
				foreach ($stmt as $col => $value) {
					if (is_numeric($value)) {
						$values .= $comma . $value;
					}
					else if (is_string($value)) {
						if (is_string($col) && $col[0] === '@') {	//	detect `raw output` modifier in column key/index/name!
							$values .= $comma . $value;
						}
						else {
							$values .= $comma . self::quote($value);
						}
					}
					else if ($value === null) {
						$values .= $comma . 'NULL';
					}
					else {
						throw new \Exception('Invalid type `' . gettype($value) .
							'` sent to VALUES([..]); only numeric, string and null are supported!');
					}
					$comma = ', ';
				}
			}
			else {
				$values = $stmt;
			}
			$this->sql .= ' VALUES (' . $values . ')';
			return $this;
		}
		return $this->prepare(' VALUES (' . $stmt . ')', ...$params);
	}





	/**
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *	https://dev.mysql.com/doc/refman/5.7/en/update.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name SET col_name={expr | DEFAULT}, ... [ ON DUPLICATE KEY UPDATE col_name=expr [, col_name=expr] ... ]
	 *		UPDATE [LOW_PRIORITY] [IGNORE] table_reference SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}]
	 *
	 *		 ... ${id} || $id (looks too much like a variable!  #{id}  :{id}   @user   (entity framework!)  {0} = parameters by index!
	 *
	 *
	 *
	 *		NOTE: Alternative 1: (['col1' => $value1, 'col2' => $value2, '@dated' => 'CURDATE()']) 		single array:		[columns => values]
	 *		NOTE: Alternative 2: (['col1', 'col2', '@dated'], [$value1, $value2, 'CURDATE()'])			two arrays:			[columns], [values]
	 *		NOTE: Alternative 3: ('col1 = ?, col2 = ?, dated = @', $value1, $value2, 'CURDATE()')
	 *		NOTE: Alternative 4: (['col1 = ?', col2 = ?, dated = @', $value1, $value2, 'CURDATE()') 	single array v2:	['column', $value, 'column', $value]
	 */
/*
	public function SET($stmt = null, ...$params)
	{

		if (is_array($stmt))
		{
			$result = null;
			$comma = null;
			if (empty($params))
			{
				if (is_numeric(key($stmt))) {
					throw new \BadMethodCallException('Invalid array type given to ->SET([...]). ' . 
						'The single array variant requires the array to have column names (as string keys) and values. ' .
						'eg. ->SET([\'col1\' => 123, \'col2\' => \'value2\', \'@col3\' => \'CURDATE()\'])');
				}
				foreach ($stmt as $col => $value)
				{
					if ($col[0] === '@') {
						$result .= $comma . substr($col, 1) . ' = ' . $value;	//	strips '@' from beginning of column names
					}
					else
					{
						if (is_numeric($value)) {
							$result .= $comma . $col . ' = ' . $value;
						}
						else if (is_string($value)) {
							$result .= $comma . $col . ' = ' . self::quote($value);
						}
						else if ($value === null) {
							$result .= $comma . $col . ' = NULL';
						}
						else {
							throw new \Exception('Invalid type `' . gettype($value) .
								'` sent to SET(); only numeric, string and null values are supported!');
							throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
											'` given to column ' . $col . ' passed in SQL->prepare(`' . $pattern .
											'`) pattern, only scalar (int, float, string, bool) and NULL values are allowed in `?` statements!');



											throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
												') supplied to SQL->prepare(`' . $pattern .
												'`) pattern! Please check the number of `?` and `@` values in the pattern; possibly requiring ' .
												(	substr_count($pattern, '?') + substr_count($pattern, '@') -
													substr_count($pattern, '??') - substr_count($pattern, '@@') -
													substr_count($pattern, '\\?') - substr_count($pattern, '\\@') -
												count($params)) . ' more value(s)');
										}
										next($params);
										$count++;

										if (is_numeric($value))	return $value;
										if (is_string($value))	return self::quote($value);
										if (is_null($value))	return 'NULL';
										if (is_bool($value))	return $value ? 'TRUE' : 'FALSE';

										prev($params);	//	key($params) returns NULL for the last entry, which produces -1 when we get the index, so we must backtrack!
										throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
														'` given at index ' . $count . ' passed in SQL->prepare(`' . $pattern .
														'`) pattern, only scalar (int, float, string, bool) and NULL values are allowed in `?` statements!');




						}
					}

					$comma = ', ';
				}
			}


			if (count($params) === 1 && is_array($params[0]))
			{
			}
			else
			{
				$col = null;
				foreach ($args as $arg)
				{
					if ($col === null) {
						$col = $arg;
						if (empty($col) || is_numeric($col))	//	basic validation ... something is wrong ... can't have a column title be empty or numeric!
							throw new \Exception('Invalid column name detected in SET(), column names must be strings! Type: `' . gettype($col) . '`, value: ' . (string) $col);
						continue;
					}

					if ($col[0] === '@') {					//	detect first character of column title ... if the title has '@' sign, then DO NOT ESCAPE! ... can be useful for 'DEFAULT', or '@id' (a connection variable) or 'MD5(...)' etc.
						$result .= $comma . substr($col, 1) . ' = ' . $value;		//	strip '@' from beginning
					}
					else {
						if (is_numeric($arg)) {
							$result .= $comma . $col . ' = ' . $arg;
						}
						else if ($arg === null) {
							$result .= $comma . $col . ' = NULL';
						}
						else if (is_string($arg)) {
							$result .= $comma . $col . ' = ' . $this->escape($arg);
						}
						else {
							throw new \Exception('Invalid type `' . gettype($arg) . '` sent to SET(); only numeric, string and null are supported!');
						}
					}
					$comma = ', ';
					$col = null;
				}
			}
			$this->sql .= ' SET ' . $result;
			return $this;
		}
		if (empty($params)) {
			$this->sql .= ' SET ' . $stmt;
			return $this;
		}
		return $this->prepare(' SET ' . $stmt, ...$params);
	}
*/
	public function SET(...$args)
	{
		$values = null;
		$comma = null;
		if (count($args) === 1 && is_array($args[0]))
		{
			foreach ($args[0] as $col => $value)
			{
				if ($col[0] === '@') {					//	detect first character of column title ... if the title has '@' sign, then DO NOT ESCAPE! ... can be useful for 'DEFAULT', or '@id' or 'MD5(...)' etc. (a connection variable) etc.
					$values .= $comma . substr($col, 1) . ' = ' . $value;		//	strip '@' from beginning
				}
				else {
					if (is_numeric($value)) {
						$values .= $comma . $col . ' = ' . $value;
					}
					else if ($value === null) {
						$values .= $comma . $col . ' = NULL';
					}
					else if (is_string($value)) {
						/**
						if ($value === 'DEFAULT') {			//	`Each value can be given as an expression, or the keyword DEFAULT to set a column explicitly to its default value.`
							$values .= $comma . $value;		//	WARNING: This is a problem! If a User calls himself 'DEFAULT' ... then what?
						}
						else if ($value === 'NULL') {		//	Should I support this level of parsing? No, I don't think so!
							$values .= $comma . $value;
						}
						else {
					//	$this->sql .= $comma . '"' . $value . '"';		//	TODO: Need to escape this!
							$values .= $comma . $this->escape($value);
						}
						*/
						$values .= $comma . $col . ' = ' . $this->escape($value);
					}
					else {
						throw new \Exception('Invalid type `' . gettype($value) . '` sent to SET(); only numeric, string and null are supported!');
					}
				}

				$comma = ', ';
			}
		}
		else
		{
			$col = null;
			foreach ($args as $arg)
			{
				if ($col === null) {
					$col = $arg;
					if (empty($col) || is_numeric($col))	//	basic validation ... something is wrong ... can't have a column title be empty or numeric!
						throw new \Exception('Invalid column name detected in SET(), column names must be strings! Type: `' . gettype($col) . '`, value: ' . (string) $col);
					continue;
				}

				if ($col[0] === '@') {					//	detect first character of column title ... if the title has '@' sign, then DO NOT ESCAPE! ... can be useful for 'DEFAULT', or '@id' (a connection variable) or 'MD5(...)' etc.
					$values .= $comma . substr($col, 1) . ' = ' . $value;		//	strip '@' from beginning
				}
				else {
					if (is_numeric($arg)) {
						$values .= $comma . $col . ' = ' . $arg;
					}
					else if ($arg === null) {
						$values .= $comma . $col . ' = NULL';
					}
					else if (is_string($arg)) {
						$values .= $comma . $col . ' = ' . $this->escape($arg);
					}
					else {
						throw new \Exception('Invalid type `' . gettype($arg) . '` sent to SET(); only numeric, string and null are supported!');
					}
				}
				$comma = ', ';
				$col = null;
			}
		}
		$this->sql .= ' SET ' . $values;
		return $this;
	}


	//	FROM thetable t, (SELECT @a:=NULL) as init;
	public function FROM(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['FROM'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['FROM'] . $stmt, ...$params);
	}
	public function F(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['FROM'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['FROM'] . $stmt, ...$params);
	}


	public function JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['JOIN'] . $stmt, ...$params);
	}
	public function J(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['JOIN'] . $stmt, ...$params);
	}

	//	WARNING: I don't think I should support this syntax!?!?
	//	better to support it in the ON() method !?!?
	//	JOIN_ON('users', 'u.id = ') ... ???
	public function JOIN_ON(string $table, ...$args)
	{
		$this->sql .= PHP_EOL . "\tJOIN {$table} ON (" . implode(', ', $args) . ')';
		return $this;
	}
	public function J_ON(string $table, ...$args)
	{
		$this->sql .= PHP_EOL . "\tJOIN {$table} ON (" . implode(', ', $args) . ')';
		return $this;
	}

	/**
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.5/en/nested-join-optimization.html
	 *		LEFT JOIN (t2, t3, t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 *			=== LEFT JOIN (t2 CROSS JOIN t3 CROSS JOIN t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 *		t1 LEFT JOIN (t2 LEFT JOIN t3 ON t2.b=t3.b OR t2.b IS NULL) ON t1.a=t2.a
	 *			=== (t1 LEFT JOIN t2 ON t1.a=t2.a) LEFT JOIN t3 ON t2.b=t3.b OR t2.b IS NULL
	 *		FROM t1 LEFT JOIN (t2 LEFT JOIN t3 ON t2.b=t3.b OR t2.b IS NULL) ON t1.a=t2.a;
	 *		FROM (t1 LEFT JOIN t2 ON t1.a=t2.a) LEFT JOIN t3 ON t2.b=t3.b OR t2.b IS NULL;
	 *		t1 LEFT JOIN (t2, t3) ON t1.a=t2.a
	 *			!==	t1 LEFT JOIN t2 ON t1.a=t2.a, t3
	 *		FROM T1 INNER JOIN T2 ON P1(T1,T2) INNER JOIN T3 ON P2(T2,T3)
	 *		FROM T1 LEFT JOIN (T2 LEFT JOIN T3 ON P2(T2,T3)) ON P1(T1,T2)
	 *		(T2 LEFT JOIN T3 ON P2(T2,T3))
	 *		T1 LEFT JOIN (T2,T3) ON P1(T1,T2) AND P2(T1,T3) WHERE P(T1,T2,T3)
	 *
	 *
	 */
	public function LEFT_JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_JOIN'] . $stmt, ...$params);
	}
	public function leftJoin(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_JOIN'] . $stmt, ...$params);
	}
	public function LJ(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_JOIN'] . $stmt, ...$params);
	}

	public function LEFT_OUTER_JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_OUTER_JOIN'] . $stmt, ...$params);
	}
	public function leftOuterJoin(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_OUTER_JOIN'] . $stmt, ...$params);
	}
	public function LOJ(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_OUTER_JOIN'] . $stmt, ...$params);
	}

	public function RIGHT_JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_JOIN'] . $stmt, ...$params);
	}
	public function rightJoin(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_JOIN'] . $stmt, ...$params);
	}
	public function RJ(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_JOIN'] . $stmt, ...$params);
	}

	public function RIGHT_OUTER_JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_OUTER_JOIN'] . $stmt, ...$params);
	}
	public function rightOuterJoin(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_OUTER_JOIN'] . $stmt, ...$params);
	}
	public function ROJ(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_OUTER_JOIN'] . $stmt, ...$params);
	}

	public function INNER_JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['INNER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['INNER_JOIN'] . $stmt, ...$params);
	}
	public function innerJoin(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['INNER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['INNER_JOIN'] . $stmt, ...$params);
	}
	public function IJ(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['INNER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['INNER_JOIN'] . $stmt, ...$params);
	}

	public function OUTER_JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['OUTER_JOIN'] . $stmt, ...$params);
	}
	public function outerJoin(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['OUTER_JOIN'] . $stmt, ...$params);
	}
	public function OJ(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['OUTER_JOIN'] . $stmt, ...$params);
	}

	public function CROSS_JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['CROSS_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['CROSS_JOIN'] . $stmt, ...$params);
	}
	public function crossJoin(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['CROSS_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['CROSS_JOIN'] . $stmt, ...$params);
	}
	public function CJ(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['CROSS_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['CROSS_JOIN'] . $stmt, ...$params);
	}

	public function STRAIGHT_JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['STRAIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['STRAIGHT_JOIN'] . $stmt, ...$params);
	}
	public function straightJoin(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['STRAIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['STRAIGHT_JOIN'] . $stmt, ...$params);
	}
	public function SJ(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['STRAIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['STRAIGHT_JOIN'] . $stmt, ...$params);
	}

	public function NATURAL_JOIN(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['NATURAL_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['NATURAL_JOIN'] . $stmt, ...$params);
	}
	public function naturalJoin(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['NATURAL_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['NATURAL_JOIN'] . $stmt, ...$params);
	}
	public function NJ(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['NATURAL_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['NATURAL_JOIN'] . $stmt, ...$params);
	}



	/**
	 *	Example:
	 *		.USING('id')
	 *		.USING('id', 'user_id')	??? legal??
	 *
	 *	Sample:
	 *		t1 LEFT JOIN t2 USING (id) LEFT JOIN t3 USING (id)
	 */
	public function USING(string ...$args)
	{
		$this->sql .= ' USING (' . implode(', ', $args) . ')';
		return $this;
	}

	/**
	 *	Examples:
	 *		LEFT JOIN (t2, t3, t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 *		LEFT JOIN (t2 CROSS JOIN t3 CROSS JOIN t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 *		t1 LEFT JOIN (t2 LEFT JOIN t3 ON t2.b=t3.b OR t2.b IS NULL) ON t1.a=t2.a
	 */
	public function ON(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= ' ON ' . $stmt;
			return $this;
		}
		return $this->prepare(' ON ' . $stmt, ...$params);
	}

	/**
	 *	Examples:
	 *		LEFT JOIN (t2, t3, t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 *		LEFT JOIN (t2 CROSS JOIN t3 CROSS JOIN t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 */
	public function AND(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= ' AND ' . $stmt;
			return $this;
		}
		return $this->prepare(' AND ' . $stmt, ...$params);
	}

	/**
	 *
	 *
	 *
	 *	Examples:
	 *		LEFT JOIN (t2, t3, t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 *		LEFT JOIN (t2 CROSS JOIN t3 CROSS JOIN t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 */
	public function OR(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= ' OR ' . $stmt;
			return $this;
		}
		return $this->prepare(' OR ' . $stmt, ...$params);
	}



	/**
	 *
	 *		->WHERE('name = ?', $name)
	 *		->WHERE(['fname = ?', $fname, 'lname = ?', $lname], 'name = ?', $name)	=> WHERE (fname = $fname) OR (lname = $lname)
	 *
	 *	Examples:
	 */
	public function WHERE(string $stmt, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['WHERE'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['WHERE'] . $stmt, ...$params);


	//	original code ...
		$this->sql .= self::$translations['WHERE'];
		for(; key($args) !== null; next($args))
		{
			$arg = current($args);
			if (mb_strpos($arg, '?') !== false) {
				for ($offset = 0; ($pos = mb_strpos($arg, '?', $offset)) !== false; $offset = $pos + 1 ) {
					$next = next($args);
					$this->sql .= mb_substr($arg, $offset, $pos - $offset) . $this->sanitize($next);
					$final = null;
				}
				$this->sql .= mb_substr($arg, $offset);
			}
			else {
				// lookahead
				$next = next($args);
				if (is_array($next)) {
					// $next member is an array of (hopefully) replacement values eg. ['id' => 5] for ':id'
					$this->sql .= mb_ereg_replace_callback(':([a-z]+)',
										function ($matches) use ($next)
										{
											if (isset($next[$matches[1]])) {
												return $this->sanitize($next[$matches[1]]);
											}
											else if (isset($next['@' . $matches[1]])) {
												return $next['@' . $matches[1]];
											}
											throw new \Exception("Unable to find index `{$matches[1]}` in " . var_export($next, true) . ' for WHILE() statement');
										}, $arg);
				}
				else {
					$this->sql .= $arg;
					prev($args);
				}
			}
		}
		$this->sql .= $final;
		return $this;
	}
	public function W(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['WHERE'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['WHERE'] . $stmt, ...$params);
	}


	/**
	 *	Escapes the input value, and replaces the '?'
	 *	
	 *	Example:
	 *		->WHERE_LIKE('', $id)
	 *
	 *
	 *	Samples:
	 *		WHERE key_col LIKE 'ab%'
	 */
	public function WHERE_LIKE(string $col, string $like)
	{
		$this->sql .= self::$translations['WHERE'] . $col . ' LIKE ' . $this->sanitize($like);
		return $this;
	}


	/**
	 *	
	 *	
	 *	Notes on mysqli::real_escape_string
	 *		http://php.net/manual/en/mysqli.real-escape-string.php#46339
	 *		`Note that this function will NOT escape _ (underscore) and % (percent) signs, which have special meanings in LIKE clauses.`
	 *
	 *		`Characters encoded are NUL (ASCII 0), \n, \r, \, ', ", and Control-Z.`
	 *
	 *
	 *
	 *	WARNING: What about cases LIKE('users.fname') ???
	 *
	 *	Example:
	 *		->LIKE('abc%')		->$sql .= "abc%"
	 *
	 *
	 *
	 *
	 *	Samples:
	 *		WHERE key_col LIKE 'ab%'
	 */
	public function LIKE(string $format, string $value = null)
	{
		$this->sql .= 'LIKE ' . $this->sanitize($like);
		return $this;
	}

	/**
	 *	`Tests whether a value is NULL.`
	 *
	 *	https://dev.mysql.com/doc/refman/5.7/en/comparison-operators.html#operator_is-null
	 *
	 *	Example:
	 *		->IS_NULL()			==> $sql .= ' IS NULL'
	 *		->IS_NULL($field)	==> $sql .= $field . ' IS NULL'
	 *
	 *	Sample:
	 *		WHERE key_col IS NULL
	 *		SELECT 1 IS NULL, 0 IS NULL, NULL IS NULL;
	 *			-> 0, 0, 1
	 */
	public function IS_NULL(string $field = null)
	{
		$this->sql .= $field . ' IS NULL';
		return $this;
	}
	public function isNull(string $field = null)
	{
		$this->sql .= $field . ' IS NULL';
		return $this;
	}

	public function WHERE_IS_NULL(string $field)
	{	//	TODO, we could detect if a value was input ... if is_null($input) ... then do something else !?!?
		$this->sql .= self::$translations['WHERE'] . $field . ' IS NULL';
		return $this;
	}


	/**
	 *	
	 *	https://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_count
	 *	
	 *	Example:
	 *		.COUNT('test_score')
	 *			$sql .= ' COUNT(test_score)';
	 *		.COUNT('test_score', 'my_min_test_score')
	 *			$sql .= ' COUNT(test_score) AS my_min_test_score';
	 *
	 *	Samples:
	 *		SELECT COUNT(*) FROM student
	 */
	public function COUNT(string $col = '*', string $as = null)
	{
		$this->sql .= 'COUNT(' . $col . ($as === null ? ')' : ') AS ' . $as);
		return $this;
	}
	/**
	 *	Forces a column alias
	 *	Automatically prepends 'min_' to the $col name if no $as was supplied!
	 *
	 *	https://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_min
	 *
	 *	Example:
	 *		.MIN('test_score')
	 *			$sql .= ' MIN(test_score)';
	 *		.MIN('test_score', 'my_min_test_score')
	 *			$sql .= ' MIN(test_score) AS my_min_test_score';
	 *
	 *	Samples:
	 *		SELECT student_name, MIN(test_score), MAX(test_score) FROM student GROUP BY student_name;
	 */
	public function COUNT_AS(string $col, string $as = null)
	{
		if ($as === null) {
			//	TODO: check $col for invalid characters if $as === null! because we need to append a col name and not some agregate function!
			
		}
		$this->sql .= 'COUNT(' . $col . ') AS ' . $as ?: ('count_of_' . $col);		///	WARNING: ... need to only get the first part of `work.artist_id`
		return $this;
	}


	/**
	 *
	 *
	 *	Example:
	 *
	 *	Samples:
	 *		
	 */
	public function AS(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= ' AS ' . $stmt;
			return $this;
		}
		return $this->prepare(' AS ' . $stmt, ...$params);
	}


	/**
	 *	`Returns the first non-NULL value in the list, or NULL if there are no non-NULL values.`
	 *	`The return type of COALESCE() is the aggregated type of the argument types.`
	 *
	 *	WARNING: This function does NOT do string output escaping!
	 *		Because string values can be literals, numbers, NULL, table/column names etc.
	 *		But most commonly it's used with table.columns
	 *
	 *	https://dev.mysql.com/doc/refman/5.7/en/comparison-operators.html#function_coalesce
	 *
	 *	Example:
	 *		->COALESCE('product.special_price', 'product.price')
	 *
	 *	Samples:
	 *		COALESCE(x, y)
	 */
	public function COALESCE(...$args)
	{
		$this->sql .= 'COALESCE(';
		$comma = null;
		foreach ($args as $arg)
		{
			if (is_scalar($arg)) {
				$this->sql .= $comma . $arg;
			}
			else if ($arg === null) {
				$this->sql .= $comma . 'NULL';
			}
			$comma = ', ';
		}
		$this->sql .= ')';
		return $this;
	}


	/**
	 *	
	 *	https://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_min
	 *	
	 *	Example:
	 *		.MIN('test_score')
	 *			$sql .= ' MIN(test_score)';
	 *		.MIN('test_score', 'my_min_test_score')
	 *			$sql .= ' MIN(test_score) AS my_min_test_score';
	 *
	 *	Samples:
	 *		SELECT student_name, MIN(test_score), MAX(test_score) FROM student GROUP BY student_name;
	 */
	public function MIN(string $col, string $as = null)
	{
		$this->sql .= 'MIN(' . $col . ($as === null ? ')' : ') AS ' . $as);
		return $this;
	}
	/**
	 *	Forces a column alias
	 *	Automatically prepends 'min_' to the $col name if no $as was supplied!
	 *
	 *	https://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_min
	 *
	 *	Example:
	 *		.MIN('test_score')
	 *			$sql .= ' MIN(test_score)';
	 *		.MIN('test_score', 'my_min_test_score')
	 *			$sql .= ' MIN(test_score) AS my_min_test_score';
	 *
	 *	Samples:
	 *		SELECT student_name, MIN(test_score), MAX(test_score) FROM student GROUP BY student_name;
	 */
	public function MIN_AS(string $col, string $as = null)
	{
		if ($as === null) {
			//	TODO: check $col for invalid characters if $as === null! because we need to append a col name and not some agregate function!
		}
		$this->sql .= 'MIN(' . $col . ') AS ' . $as ?: ('min_' . $col);
		return $this;
	}
	/**
	 *	
	 *	https://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_min
	 *	
	 *	Example:
	 *		.MIN_DISTINCT('test_score')
	 *			$sql .= ' MIN(DISTINCT test_score)';
	 *		.MIN_DISTINCT('test_score', 'my_min_test_score')
	 *			$sql .= ' MIN(DISTINCT test_score) AS my_min_test_score';
	 *
	 *	Samples:
	 *		SELECT student_name, MIN(test_score), MAX(test_score) FROM student GROUP BY student_name;
	 */
	public function MIN_DISTINCT(string $col, string $as = null)
	{
		$this->sql .= 'MIN(DISTINCT ' . $col . ($as === null ? ')' : ') AS ' . $as);
		return $this;
	}
	/**
	 *	Forces a column alias
	 *	Automatically prepends 'min_' to the $col name if no $as was supplied!
	 *
	 *	https://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_min
	 *
	 *	Example:
	 *		.MIN_DISTINCT_AS('test_score', 'my_min_test_score')
	 *			$sql .= ' MIN(DISTINCT test_score) AS my_min_test_score';
	 *		.MIN_DISTINCT_AS('test_score')
	 *			$sql .= ' MIN(DISTINCT test_score) AS min_test_score';
	 *
	 *	Samples:
	 *		SELECT student_name, MIN(test_score), MAX(test_score) FROM student GROUP BY student_name;
	 */
	public function MIN_DISTINCT_AS(string $col, string $as = null)
	{
		$this->sql .= 'MIN(DISTINCT ' . $col . ') AS ' . $as ?: ('min_' . $col);
		return $this;
	}


	/**
	 *	
	 *	https://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_max
	 *
	 *	Example:
	 *		.MAX(5)
	 *
	 *	Samples:
	 *		
	 */
	public function MAX(string $max)
	{
		throw new \Exception('Need to copy the MIN() handlers to MAX() when I am done!');
		$this->sql .= 'MAX(' . $max . ')';
		return $this;
	}

	/**
	 *	
	 *	
	 *	Example:
	 *		.SUM('price')
	 *
	 *	Samples:
	 *		
	 */
	public function SUM(string $col, string $as = null)
	{
		$this->sql .= 'SUM(' . $col . ')' . ($as === null ? null : ' AS ' . $as);
		return $this;
	}

	/**
	 *	
	 *	
	 *	Example:
	 *		.SUM('price')
	 *
	 *	Samples:
	 *		
	 */
	public function SUM_AS(string $col, string $as)
	{
		$this->sql .= 'SUM(' . $col . ') AS ' . $as;
		return $this;
	}

	/**
	 *	
	 *	
	 *	Example:
	 *		.SUM('price')
	 *
	 *	Samples:
	 *		DELETE FROM t WHERE i IN(1,2);
	 *		
	 *		
	 */
	public function IN(...$args)			//	WARNING: Not sure how to best handle this yet! prepare() or like this ?
	{
/*
		if (empty($params)) {
			$this->sql .= ' AS ' . $stmt;
			return $this;
		}
		return $this->prepare(' AS ' . $stmt, ...$params);
*/

		$comma = null;
		$this->sql .= ' IN (';
		foreach ($args as $arg)
		{
			$this->sql .= $comma . $this->sanitize($arg);
			$comma = ', ';
		}
		$this->sql .= ')';
		return $this;
	}


	/**
	 *	2 Styles! If only 2x parameters are specified, then we skip adding the field before!
	 *		$arg1 . ' BETWEEN ' . $arg2 . ' AND ' . $arg3
	 *		' BETWEEN ' . $arg1 . ' AND ' . $arg2
	 *
	 *	WARNING: I think I've had an issue once where I used some kind of sum/agregate and the values needed to be in (...)
	 *	
	 *	Example:
	 *		->BETWEEN('age', $min, $max)
	 *		->WHERE->age->BETWEEN($min, $max)
	 *		->WHERE('age')->BETWEEN($min, $max)
	 *
	 *	Samples:
	 *		WHERE UnitPrice BETWEEN 15.00 AND 20.00
	 *		WHERE ProductName BETWEEN "A" and "D"
	 */
	public function BETWEEN($arg1, $arg2, $arg3 = null)
	{
		$this->sql .= $arg3 === null ? (' BETWEEN ' . $arg1 . ' AND ' . $arg2) : ($arg1 . ' BETWEEN ' . $arg2 . ' AND ' . $arg3);
		return $this;
	}


	/**
	 *	
	 *	
	 *	Example:
	 *		->CLAMP('price', $min, $max)
	 *
	 *	Samples:
	 *		max($min, min($max, $current));
	 */
	public function CLAMP(string $value, $min, $max, string $as = null)
	{
		$this->sql .= 'MIN(MAX(' . $value . ', ' . $min . '), ' . $max . ')' . ($as === null ? null : ' AS ' . $as);
		return $this;
	}


	/**
	 *
	 *
	 *	Example:
	 *		->CLAMP_AS('price', $min, $max, 'price_range')
	 *
	 *	Samples:
	 *		max($min, min($max, $current));
	 */
	public function CLAMP_AS(string $value, $min, $max, string $as)
	{
		$this->sql .= 'MIN(MAX(' . $value . ', ' . $min . '), ' . $max . ') AS ' . $as;
		return $this;
	}


	/**
	 *	
	 *	
	 *	Example:
	 *		->UNION()
	 *		->UNION('SELECT * FROM users')
	 *		->UNION()->SELECT('* FROM users')
	 *		->UNION()->SELECT('*').FROM('users')
	 *
	 *	Samples:
	 *		WHERE key_col LIKE 'ab%'
	 */
	public function UNION(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['UNION'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['UNION'] . $stmt, ...$params);
	}


	/**
	 *	
	 *	
	 *	Example:
	 *		.ORDER_BY
	 *
	 *
	 *	Samples:
	 *		ORDER BY key_part1, key_part2
	 *		ORDER BY key_part2
	 *		ORDER BY key_part1 DESC, key_part2 DESC
	 *		ORDER BY key_part1 DESC, key_part2 DESC
	 *		ORDER BY key_part1 ASC
	 *		ORDER BY key_part1 DESC
	 *		ORDER BY key_part2
	 *		ORDER BY key1, key2
	 *		ORDER BY ABS(key)
	 *		ORDER BY -key
	 *		ORDER BY NULL
	 *		ORDER BY a, b
	 */
	public function ORDER_BY(string ...$args)
	{
		$this->sql .= self::$translations['ORDER_BY'];
		$comma = null;
		foreach ($args as $arg)
		{
			if ($comma === null)
			{	// faster test for ORDER BY with only one column, or only one value, and no strtoupper() conversion
				$this->sql .= $arg;
				$comma = ', ';
			}
			else
			{
				switch (trim(strtoupper($arg)))
				{
					case 'DESC':
					case 'ASC':
						//	skip adding commas for `DESC` and `ASC`
						//	eg. ORDER_BY('price', 'DESC') => price DESC => and not => price, DESC
						$this->sql .= ' ' . trim($arg);
						break;
					default:
						$this->sql .= $comma . $arg;
				}
			}
		}
		return $this;
	}
	public function orderBy(string ...$args)
	{
		$this->sql .= self::$translations['ORDER_BY'];
		$comma = null;
		foreach ($args as $arg)
		{
			if ($comma === null)
			{	// faster test for ORDER BY with only one column, or only one value, and no strtoupper() conversion
				$this->sql .= $arg;
				$comma = ', ';
			}
			else
			{
				switch (trim(strtoupper($arg)))
				{
					case 'DESC':
					case 'ASC':
						//	skip adding commas for `DESC` and `ASC`
						//	eg. ORDER_BY('price', 'DESC') => price DESC => and not => price, DESC
						$this->sql .= ' ' . trim($arg);
						break;
					default:
						$this->sql .= $comma . $arg;
				}
			}
		}
		return $this;
	}

	/**
	 *	LIMIT syntax has 2 variations:
	 *		[LIMIT {[offset,] row_count | row_count OFFSET offset}]
	 *		LIMIT 5
	 *		LIMIT 5, 10
	 *		LIMIT 10 OFFSET 5
	 *	
	 *	Example:
	 *		.LIMIT(5)
	 *		.LIMIT(10, 5)
	 *		.LIMIT(5)->OFFSET(10)
	 *
	 *	Samples:
	 */
	public function LIMIT(int $v1, int $v2 = null)
	{
		$this->sql .= self::$translations['LIMIT'] . $v1 . ($v2 === null ? null : ', ' . $v2);
		return $this;
	}

	/**
	 *	LIMIT syntax has 2 variations:
	 *		[LIMIT {[offset,] row_count | row_count OFFSET offset}]
	 *		LIMIT 5
	 *		LIMIT 10, 5
	 *		LIMIT 5 OFFSET 10
	 *
	 *	Example:
	 *		->LIMIT(5)
	 *		->LIMIT(10, 5)
	 *		->LIMIT(5)->OFFSET(10)
	 *
	 *	Samples:
	 *		
	 */
	public function OFFSET(int $offset)
	{
		$this->sql .= ' OFFSET ' . $offset;
		return $this;
	}

	/**
	 *	
	 *
	 *	Example:
	 *		.sprintf()
	 *
	 *	Samples:
	 *		
	 */
	public function sprintf(...$args)			//	http://php.net/manual/en/function.sprintf.php
	{
		$this->sql .= sprintf(...$args);		//	TODO: Detect `?` and parse the string first ???
		return $this;
	}

	/**
	 *	
	 *
	 *	Example:
	 *		.bind()
	 *
	 *	Samples:
	 *		WHERE book.ID >= :p1 AND book.ID <= :p2)'; // :p1 => 123, :p2 => 456		WHERE book.AUTHOR_ID IN (:p1, :p2)'; // :p1 => 123, :p2 => 456
	 */
	public function bind(...$args)				//	http://php.net/manual/en/function.sprintf.php
	{
		throw new \Exception('TODO: bind() parameters with ?');
		$this->sql .= sprintf(...$args);		//	TODO: Detect `?` and parse the string first ???
		return $this;
	}


	/**
	 *	Prepare a given input string with given parameters
	 *
	 *	WARNING: This function doesn't replace the PDO::prepare() statement for security, only convenience!
	 *
	 *	To disable value escaping, use one of the following techniques:
	 *			->prepare('sp_name(LAST_INSERT_ID(), @, @, ?:varchar:4000, %s:40, %d)', 'u.name', '@sql_variable', $name)
	 *			->prepare('sp_name', ['@' => 'LAST_INSERT_ID()'])
	 *			->prepare('sp_name(@, ?)', 'LAST_INSERT_ID()', $name)
	 *			->prepare('SELECT sp_name(@, ?)', 'LAST_INSERT_ID()', $name)
	 *			->prepare('SELECT sp_name(LAST_INSERT_ID(), ?)', $name)
	 *
	 *			SQL()->prepare('#date{nullable:call:msg:onerror:msg}', $date, function(){...});
	 *			SQL()->prepare('#date', $date, function(){...});
	 *
	 *	Examples:
	 *		INTO('users', 'col1', 'col2', 'col3')
	 *		INTO('users', ['col1', 'col2', 'col3'])
	 *		INTO('users', ['col1' => 'value1', 'col2' => 'value2', 'col3' => 'value3'])
	 *		INTO('users', ['col1', 'col2', 'col3'], ['value1', 'value2', 'value3'])
	 *
	 *	SQL Syntax:
	 *		PDO:
	 *			$stmt = $pdo->prepare("CALL sp_returns_string(?)");
	 *			$stmt->bindParam(1, $return_value, PDO::PARAM_STR, 4000);
	 *			$stmt->execute();

		Syntax WISHLIST

			%e{##-##-##}
			%{format}date(time)
			{{%timestamp}}
			%q					%quot			test for string	 or 	%q:n	allows null as well!
			{{%datetimez}}
			{{%money}}
			{{%currency}}
			%{min,max}int		%int{min:max}		%int{:max} %int{min:}		%int:100,
			%clamp{n:100}		%clamp{null:100}	default is MAX value ... allows null values
			%clamp{100}			%clamp{100}			default is MAX value
			%clamp{:100}		%clamp{,100}		same as default
			%clamp{0:}			%clamp{0,}
			%clamp{0:100}		%clamp{0,100}		%clamp:1:100
			%float{0..1}		range check															:default:1.0   (when null!)		:def:1.0
			%range{0..1}		range test, call callback on failure or throw exception!
			%clamp{0:100}		%clamp{0,100}
			%varchar{4000}		{max}	default
			%varchar{:4000}		same as default				:enull		:raw  :ufirst :lower(case) :uwords :upper(case)			:noquot ??? 	:noescape ???	(can be used with crop and pack/trim)
			%varchar{8:4000}	{min:max}
			%varchar{8:}		specify min
			%match{\d\d}		%match:n:call{\d{3}}	%match:n:call{~\d{3}~}
			%onerror			-	next value is an error handler callback
			%after{DATE}		after{tomorrow} after{2017-01-01}								https://laravel.com/docs/5.4/validation#rule-after
			%after_or_equal{DATE}																https://laravel.com/docs/5.4/validation#rule-after-or-equal
			%alpha																				https://laravel.com/docs/5.4/validation#rule-alpha
			%alpha_dash																			https://laravel.com/docs/5.4/validation#rule-alpha-dash
			%alpha_num																			https://laravel.com/docs/5.4/validation#rule-alpha-num
			%array																				https://laravel.com/docs/5.4/validation#rule-array
			%before:date																		https://laravel.com/docs/5.4/validation#rule-before
			%before_or_equal:date																https://laravel.com/docs/5.4/validation#rule-before-or-equal
			%between{min,max}				AKA `clamp`											https://laravel.com/docs/5.4/validation#rule-between
			%boolean																			https://laravel.com/docs/5.4/validation#rule-boolean	The field under validation must be able to be cast as a boolean. Accepted input are true, false,  1, 0, "1", and "0".
			%confirmed																			https://laravel.com/docs/5.4/validation#rule-confirmed	... can provide a way to check if the value matches another value ... with {1} or whatever, provide an index number to the params ???
			%isip				#is_ip
			%isint				#is_int
			%isjson				#is_json
			%tojson				#to_json
			%fromjson			#from_json
			%json_encode		#json_encode
			%jsonencode			#jsonencode
			%jsonify			#jsonify
			%serialize			#serialize
			%string{trim:ltrim:rpad: :5:json_encode}
			%escape				Escape only, doesn't add quotes "..."				:enull === empty equals null
			%quot{nullable}		Escape AND quote "..."
			%raw
			%email{call}
			%call{message}
			%char
			%hex
			%lower
			%upper
			%{male , female}enum	or 	%{male : female}enum	#enumi = insensitive | #ienum
			%{male , female}set		or 	%{male : female}set
			%{value1, value2, value3, value4}index
			%bit{Y:N}					problem: how to define 'nullable' if we normalize the strings ??? because if we have %bit{:n:y:n} ... it's ambiguous!
			%bool(ean){YES:NO}
	 *
	 *	Support for WHERE('owner_id IN (?)', $array)	... (?) = array placeholder	  ([]) ???  	from Ruby!   ... (#?) || (#) == numeric array (faster?)  (#, 1, 2, 3) || (1, 2, 3, #, 4, 5, 6) ??? NO!
	 *		I prefer ->WHERE('owner_id IN ([])', $array)
	 *
	 *	Array placeholder [], values are simply comma separated ... [@] = raw array, [#] numeric array ? (faster?)   ... [#, 1, 2, 3] || [1, 2, 3, #] || [NULL, #]
	 *
	 *	@param string $pattern eg. 'CALL sp_name(LAST_INSERT_ID(), @, @, ?:varchar:4000)'
	 *	@param string|numeric|null $params Input values to replace and/or escape
	 *	@return $this
	 */
	public function prepare(string $pattern, ...$params)	//	\%('.+|[0 ]|)([1-9][0-9]*|)s		somebody else's sprintf('%s') multi-byte conversion ... %s includes the ability to add padding etc.
	{
		$count = 0;
		if (count($params) === 1 && is_array($params[0])) {		//	allowing: ->prepare('SELECT name FROM user WHERE id IN (?, ?, ?)', [1, 2, 3])
			$params = $params[0];								//	problem is when the first value is for :json_encode ... we can allow ONE decode ?
			$params_conversion = true;							//	basically, this value is to support :json_encode, when there is only ONE value passed, then $params become our value, and not $params[0]!
		}
		$this->sql .= mb_ereg_replace_callback('\?\?|\\?|\\\%|%%|\\@|@@|\?|@[^a-zA-Z]?|\[.*\]|%([a-z][_a-z]*)(\:[a-z0-9\.\-:]*)*(\{[^\{\}]+\})?|%sn?(?::?\d+)?|%d|%u(?:\d+)?|%f|%h|%H|%x|%X',
							function ($matches) use (&$count, $pattern, &$params)
							{
dump($matches);
								$match = $matches[0];
								switch($match[0])
								{
									case '?':
										if ($match === '??' || $match === '\\?')
											return '?';

										$value = current($params);
										if ($value === false && key($params) === null) {
											throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
												') supplied to SQL->prepare(`' . $pattern .
												'`) pattern! Please check the number of `?` and `@` values in the pattern; possibly requiring ' .
												(	substr_count($pattern, '?') + substr_count($pattern, '@') -
													substr_count($pattern, '??') - substr_count($pattern, '@@') -
													substr_count($pattern, '\\?') - substr_count($pattern, '\\@') -
												count($params)) . ' more value(s)');
										}
										next($params);
										$count++;

										if (is_numeric($value))	return (string) $value;
										if (is_string($value))	return self::quote($value);
										if (is_null($value))	return 'NULL';
										if (is_bool($value))	return $value ? '1' : '0';	//	bool values return '' when false

										prev($params);	//	key($params) returns NULL for the last entry, which produces -1 when we get the index, so we must backtrack!
										throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
														'` given at index ' . key($params) . ' passed to SQL->prepare(`' . $pattern .
														'`) pattern, only scalar (int, float, string, bool) and NULL values are allowed in `?` statements!');

									case '@':	//	similar to ?, but doesn't include "" around strings, ie. literal/raw string
										if ($match === '@@' || $match === '\\@')
											return '@';

										$value = current($params);
										if ($value === false && key($params) === null) {
											throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
												') supplied to SQL->prepare(`' . $pattern .
												'`) pattern! Please check the number of `?` and `@` values in the pattern; possibly requiring ' .
												(	substr_count($pattern, '?') + substr_count($pattern, '@') -
													substr_count($pattern, '??') - substr_count($pattern, '@@') -
													substr_count($pattern, '\\?') - substr_count($pattern, '\\@') -
												count($params)) . ' more value(s)');
										}
										next($params);
										$count++;

										if (is_string($value))	return $value;	//	first test because it's the most common for @
										if (is_numeric($value))	return (string) $value;
										if (is_null($value))	return 'NULL';
										if (is_bool($value))	return $value ? '1' : '0';	//	bool values return '' when false

										prev($params);	//	key($params) returns NULL for the last entry, which produces -1 when we get the index, so we must backtrack!
										throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
														'` given at index ' . key($params) . ' passed to SQL->prepare(`' . $pattern .
														'`) pattern, only scalar (int, float, string, bool) and NULL values are allowed in `@` (raw output) statements!');

								//	case '%':
									default:
										$command = $matches[1];
										if ($command === '')	//	for '%%' && '\%', $match === $matches[0] === "%%" && $command === $matches[1] === ""
											return '%';

										$value = current($params);
										$index = key($params);			//	key($params) returns NULL for the last entry, which produces -1 when we get the index, so we must backtrack!
										if ($value === false && $index === null) {
											throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
												') supplied to SQL->prepare(`' . $pattern .
												'`) pattern! Please check the number of `?`, `@` and `%` values in the pattern!');
										}
										$next = next($params);
										//	detect `call(able)` method in $next and skip!
										//	because some commands might accept a `callable` for error handling
										if (is_callable($next))
											next($params);	// skip the callable by moving to next parameter!
										$count++;

										if ( ! empty($matches[3]))
											$matches[3] = rtrim(ltrim($matches[3], '{'), '}');
										$modifiers = $matches[2] . (empty($matches[3]) ? null : ':' . $matches[3]);

										if (is_null($value)) {
											//	working, but (future) support for regular expressions might create false positives
											if (preg_match('~[\{:]n(ull(able)?)?([:\{\}]|$)~', $modifiers)) {
												return 'NULL';
											}
											throw new \InvalidArgumentException('NULL value detected for a non-nullable field at index ' . $index . ' for command: `' . $matches[0] . '`');
										}

										if (isset(self::$modifiers[$command]))
										{
											if (call_user_func(self::$types[$command], $value, $modifiers, 'init')) {
												return $value;
											}
										}

										if (isset(self::$types[$command]))
										{
										//	Cannot use call_user_func() with a value reference ... 2 different errors ... one when I try `&$value`
										//	Parse error: syntax error, unexpected '&' in Sql.php on line ...
										//	Warning: Parameter 1 to {closure}() expected to be a reference, value given in Sql.php on line ...
										//	$result = call_user_func(self::$types[$command], $value, $modifiers);
											$result = self::$types[$command]($value, $modifiers);
											if (is_string($result)) {
												return $result;
											}
										}

										switch ($command)
										{
											case 'string':
											case 'varchar':				//	varchar:trim:crop:8:100 etc. ... to enable `cropping` to the given sizes, without crop, we throw an exception when the size isn't right! and trim to trim it!
											case 'char':				//	:normalize:pack:tidy:minify:compact ... pack the spaces !?!? and trim ...  `minify` could be used for JavaScript/CSS etc.
											case 'text':				//	I think we should use `text` only to check for all the modifiers ... so we don't do so many tests for common %s values ... this is `text` transformations ...
											case 's':

												//	WARNING: We need to handle the special case of `prepare('%s:json_encode', ['v2', 'v2'])` ... where the first param is an array ...

												//	empty string = NULL
												if (strpos($modifiers, ':json') !== false)
												{
dump($params);
dump($value);
													if (isset($params_conversion) && $params_conversion) {	//	the first $param[0] WAS an array (as test at the top) ... and there was only one value ...
														$value	=	$params;								//	$params IS an array and IS our actual value, not the first value OF params!
													}
dump($params);
dump($value);
													if (is_array($value)) {
														//	loop through the values and handle :trim :pack etc. on them
														if (strpos($modifiers, ':pack') !== false) {
															foreach ($value as $json_key => $json_value) {
																if (is_string())
																	$json_value = trim(mb_ereg_replace('\s+', ' ', $value));
																else if (is_numeric())
																	$json_value = trim(mb_ereg_replace('\s+', ' ', $value));
															}
														}
														else if (strpos($modifiers, ':trim') !== false) {
															foreach ($value as $json_key => $json_value) {
																$json_value = trim(mb_ereg_replace('\s+', ' ', $value));
															}
														}
													}
dump($params);
dump($value);
													//	ordered by most common
													if (strpos($modifiers, ':jsonencode') !== false) {
														$value = json_encode($value);
													}
													else if (strpos($modifiers, ':json_encode') !== false) {	//	`_` is giving problems in the regular expression! Dunno why!
														$value = json_encode($value);
													}
													else if (strpos($modifiers, ':jsonify') !== false) {
														$value = json_encode($value);
													}
													else if (strpos($modifiers, ':to_json') !== false) {
														$value = json_encode($value);
													}
													else if (strpos($modifiers, ':json_decode') !== false) {	//	WARNING: only string values in :json_decode are valid! So it has limited application!
														$value = json_decode($value);
													}
													else if (strpos($modifiers, ':from_json') !== false) {
														$value = json_decode($value);
													}
													else if (strpos($modifiers, ':fromjson') !== false) {
														$value = json_decode($value);
													}
												}
dump($params);
dump($value);

												if ( ! is_string($value)) {
													throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
																	'` given at index ' . $index . ' passed in SQL->prepare(`' . $pattern .
																	'`) pattern, only string values are allowed for %s statements!');
												}

											//	$modifiers = array_flip(explode(':', $modifiers));	//	strpos() is probably still faster!

												if (strpos($modifiers, ':pack') !== false) {
													$value = trim(mb_ereg_replace('\s+', ' ', $value));
												} else if (strpos($modifiers, ':trim') !== false) {
													$value = trim($value);
												}

												//	empty string = NULL
												if (strpos($modifiers, ':enull') !== false && empty($value)) {
													return 'NULL';
												}

												if ($command === 'text') {	//	`text` only modifiers ... not necessarily the `text` data types, just extra `text` modifiers
													if (strpos($modifiers, ':tolower') !== false || strpos($modifiers, ':lower') !== false || strpos($modifiers, ':lcase') !== false) {
														$value = mb_strtolower($value);
													}

													if (strpos($modifiers, ':toupper') !== false || strpos($modifiers, ':upper') !== false || strpos($modifiers, ':ucase') !== false) {
														$value = mb_strtoupper($value);
													}

													if (strpos($modifiers, ':ucfirst') !== false) {
														$value = mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
													}

													if (strpos($modifiers, ':ucwords') !== false) {
														$value = mb_convert_case($value, MB_CASE_TITLE);
													}

													if (strpos($modifiers, ':md5') !== false) {	//	don't :pack if you are hashing passwords!
														$value = md5($value);
													}

													if (strpos($modifiers, ':sha') !== false) {
														if (strpos($modifiers, ':sha1') !== false) {
															$value = hash('sha1', $value);
														} else if (strpos($modifiers, ':sha256') !== false) {
															$value = hash('sha256', $value);
														} else if (strpos($modifiers, ':sha384') !== false) {
															$value = hash('sha384', $value);
														} else if (strpos($modifiers, ':sha512') !== false) {
															$value = hash('sha512', $value);
														}
													}
												}

												preg_match('~(?:(?::\d*)?:\d+)~', $modifiers, $range);
												/**
												 *	"%varchar:1.9:-10."
												 *		":1.9:-10"
												 *
												 *	"%varchar:-9.9:-1:n"
												 *		":-9.9:-1"
												 *
												 *	"%varchar:.0:n"
												 *		":.0"
												 *
												 *	"%varchar:1.0:0"
												 *		":1.0:0"
												 *
												 *	"%varchar:n::10"
												 *		"::10"
												 */
												if ( ! empty($range)) {
													$range = ltrim($range[0], ':');
													if (is_numeric($range)) {
														$min = 0;
														$max = $range;
													} else {
														$range = explode(':', $range);
														if ( count($range) !== 2 || ! empty($range[0]) && ! is_numeric($range[0]) || ! empty($range[1]) && ! is_numeric($range[1])) {
															throw new \InvalidArgumentException("Invalid syntax detected for `%{$command}` statement in `{$matches[0]}`
																			given at index {$index} for SQL->prepare(`{$pattern}`) pattern;
																			`%{$command}` requires valid numeric values. eg. %{$command}:10 or %{$command}:8:50");
														}
														$min = $range[0];
														$max = $range[1];
													}

													$strlen = mb_strlen($value);
													if ($min && $strlen < $min) {
															throw new \InvalidArgumentException("Invalid string length detected for `%{$command}` statement in
																			`{$matches[0]}` given at index {$index} for SQL->prepare(`{$pattern}`) pattern;
																			`{$matches[0]}` requires a string to be a minimum {$min} characters in length; input string has only {$strlen} of {$min} characters");
													}
													if ( $max && $strlen > $max) {
//dump($normalized);
														if (strpos($modifiers, ':crop') !== false) {
															$value = mb_substr($value, 0, $max);
														}
														else {
															throw new \InvalidArgumentException("Invalid string length detected for `%{$command}` statement in `{$matches[0]}`
																			given at index {$index} for SQL->prepare(`{$pattern}`) pattern; `{$matches[0]}` requires a string to be maximum `{$max}`
																			size, and cropping is not enabled! To enable auto-cropping specify: `{$command}:{$min}:{$max}:crop`");
														}
													}
												}

												//	:raw = :noquot + :noescape
												if (strpos($modifiers, ':raw') !== false) {
													return $value;
												}

												$noquot		= strpos($modifiers, ':noquot')	!== false;
												$noescape	= strpos($modifiers, ':noescape')	!== false;
												$utf8mb4	= strpos($modifiers, ':utf8mb4')	!== false || strpos($modifiers, ':noclean') !== false;	// to NOT strip 4-byte UTF-8 characters (MySQL has issues with them and utf8 columns, must use utf8mb4 table/column and connection, or MySQL will throw errors)

												return ($noquot ? null : self::$quot) . ($noescape ? $value : self::escape($utf8mb4 ? $value : self::utf8($value))) . ($noquot ? null : self::$quot);


											case 'd':
											case 'f';
											case 'e';
											case 'float';
											case 'id':
											case 'int':
											case 'byte':
											case 'bit':
											case 'integer':
											case 'unisigned';

												if (is_numeric($value))
												{
													if (strpos($modifiers, ':clamp') !== false)
													{
														preg_match('~:clamp:(?:([-+]?[0-9]*\.?[0-9]*):)?([-+]?[0-9]*\.?[0-9]*)~', $modifiers, $range);
														if (empty($range)) {
															throw new \InvalidArgumentException("Invalid %{$command}:clamp syntax `{$matches[0]}`
																		detected for call to SQL->prepare(`{$pattern}`) at index {$index};
																		%{$command}:clamp requires a numeric range: eg. %{$command}:clamp:10 or %{$command}:clamp:1:10");
														}
														$value = min(max($value, is_numeric($range[1]) ? $range[1] : 0), is_numeric($range[2]) ? $range[2] : PHP_INT_MAX);
													}
													return $value;
												}

												throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
																'` given at index ' . $index . ' passed in SQL->prepare(`' . $pattern .
																'`) pattern, only numeric data types (integer and float) are allowed for %d and %f statements!');

											case 'clamp';

												if ( ! is_numeric($value)) {
													throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
																	'` given at index ' . $index . ' passed in SQL->prepare(`' . $pattern .
																	'`) pattern, only numeric data types (integer and float) are allowed for %clamp statements!');
												}

												preg_match('~(?:(?::[-+]?[0-9]*\.?[0-9]*)?:[-+]?[0-9]*\.?[0-9]+)~', $modifiers, $range);
												/**
												 *	"%clamp:1.9:-10."
												 *		":1.9:-10"
												 *
												 *	"%clamp:-9.9:-1:n"
												 *		":-9.9:-1"
												 *
												 *	"%clamp:.0:n"
												 *		":.0"
												 *
												 *	"%clamp:1.0:0"
												 *		":1.0:0"
												 *
												 *	"%clamp:n::10"
												 *		"::10"
												 */

												if (empty($range)) {
													throw new \InvalidArgumentException('Invalid %clamp syntax `' . $matches[0] .
																'` detected for call to SQL->prepare(`' . $pattern .
																'`) at index ' . $index . '; %clamp requires a numeric range: eg. %clamp:1:10');
												}
												$range = ltrim($range[0], ':');
												if (is_numeric($range)) {
													$value = min(max($value, 0), $range);
												} else {
													$range = explode(':', $range);
													if ( count($range) !== 2 || ! empty($range[0]) && ! is_numeric($range[0]) || ! empty($range[1]) && ! is_numeric($range[1])) {
														throw new \InvalidArgumentException('Invalid syntax detected for %clamp statement in `' . $matches[0] .
																		'` given at index ' . $index . ' for SQL->prepare(`' . $pattern .
																		'`) pattern; %clamp requires valid numeric values. eg. %clamp:0.0:1.0 or %clamp:1:100 or %clamp::100 or %clamp:-10:10');
													}
													$value = min(max($value, $range[0]), $range[1]);
												}

												return $value;

											case 'bool':
											case 'boolean':

											case 'date':
											case 'datetime';
											case 'timestamp';
										}
										return $value;
								}

//								throw new \Exception("Unable to find index `{$matches[1]}` in " . var_export($next, true) . ' for WHILE() statement');
							}, $pattern);
		if ($count !== count($params)) {
			throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
				') supplied to SQL->prepare(`' . $pattern .
				'`) pattern! Explecting ' . $count . ' for this pattern!');
		}
	}
/*
		$this->sql .= PHP_EOL . 'WHERE ';
		for(; key($args) !== null; next($args))
		{
			$arg = current($args);
			if (mb_strpos($arg, '?') !== false) {
				for ($offset = 0; ($pos = mb_strpos($arg, '?', $offset)) !== false; $offset = $pos + 1 ) {
					$next = next($args);
					$this->sql .= mb_substr($arg, $offset, $pos - $offset) . $this->sanitize($next);
					$final = null;
				}
				$this->sql .= mb_substr($arg, $offset);
			}
			else {
				// lookahead
				$next = next($args);
				if (is_array($next)) {
					// $next member is an array of (hopefully) replacement values eg. ['id' => 5] for ':id'
					$this->sql .= mb_ereg_replace_callback(':([a-z]+)',
										function ($matches) use ($next)
										{
											if (isset($next[$matches[1]])) {
												return $this->sanitize($next[$matches[1]]);
											}
											else if (isset($next['@' . $matches[1]])) {
												return $next['@' . $matches[1]];
											}
											throw new \Exception("Unable to find index `{$matches[1]}` in " . var_export($next, true) . ' for WHILE() statement');
										}, $arg);
				}
				else {
					$this->sql .= $arg;
					prev($args);
				}
			}
		}
		$this->sql .= $final;
		return $this;
*/



	/**
	 *	
	 *		http://php.net/manual/en/function.explode.php
	 *
	 *	Example:
	 *		.implode()
	 *
	 *	Samples:
	 *		
	 */
	public function implode(...$args)
	{
		$this->sql .= implode(...$args);
		return $this;
	}

	/**
	 *	
	 *		http://php.net/manual/en/function.bin2hex.php
	 *
	 *	Example:
	 *		.hexify()
	 *
	 *	Samples:
	 *
	 */
	public function hexify($str)
	{
		$this->sql .= ' 0x' . bin2hex($str);
		return $this;
	}



    /**
     * Returns an entry of the container by its name.
     *
     * @param string $name Entry name or a class name.
     *
     * @throws InvalidArgumentException The name parameter must be of type string.
     * @throws DependencyException Error while resolving the entry.
     * @throws NotFoundException No entry found for the given name.
     * @return mixed
     */
//    public function get($name)
	/**
	 *	https://dev.mysql.com/doc/refman/5.7/en/select.html
	 *	
	 *	Example:
	 *		->SELECT->('*')->FROM->('users')
	 *		->SELECT->COUNT_ALL->AS->count_of
	 *			->FROM->users
	 *		->SELECT->ALL->FROM->('users')
	 *		->SELECT->UNION->SELECT->('*')->AS->()
	 *		->SELECT_DISTINCT_ALL_FROM_users_WHERE .... WTF
	 *
	 *	Samples:
	 *		
	 */
	public function __get($name)
	{
		if (isset(self::$translations[$name])) {
			$this->sql .= self::$translations[$name];
		}
		else if (strlen($name) === strspn($name, '_etaoinshrdlcumwfgypbvkjxqz')) 	//	lowercase letters orderd by letter frequency in the English language:	https://en.wikipedia.org/wiki/Letter_frequency
		{
			if ($name === '___') { // special open-close operator
				//	TODO: considering letting ___ (3x underscores) represent open and close brackets, but not very interesting feature!
			}
			else {
				//	string contains ALL lowercase values and underscores ... ie. probably a table/field/column name! Leave unchanged!
				$this->sql .= $name;
			}
		}
		else if (is_numeric(str_replace('_', '', $name))) //   strlen($name) === strspn($name, '_0123456789'))
		{
			$this->sql .= str_replace('_', '', trim($name));	//	->_5 || ->_4_5_6 -> 4, 5, 6
		}
		else {
			$this->sql .= $name;
		}
		return $this;
	}

	/**
	 *
	 *	$sql = SQL()->();
	 *	$sql = SQL()->SELECT->STAR->FROM->users->();	//	'SELECT * FROM users'
	 *	$sql = SQL()->ORDER_BY->price->DESC->();		//	'ORDER BY price DESC'
	 */
	public function __invoke(...$args)
	{
	//	if (count($args) === 0) {	// not sure about this !?!?
	//		return $this->sql;
	//	}
		$this->sql .= implode(null, $args);
		return $this;
	}

	/**
	 *	$sql = SQL()->();
	 *	$sql = SQL()->SELECT->STAR->FROM->users->();	//	'SELECT * FROM users'
	 *	$sql = SQL()->ORDER_BY->price->DESC->_();		//	'ORDER BY price DESC'
	 */
	public function _(...$args)
	{
		switch ($this->context)
		{
			case 'CALL':	// ... can provide custom handling for this context ... like 'CALL sp_name([parameter[,...]])' ===>>	->CALL_spGetCustomData->($value1, $value2)
							//	for CALL, maybe we OPEN AND CLOSE the '(' ... ')'
				break;
		}

		if (count($args) > 0)
		{
			if (count($args) > 1)
			{
				
			}
			$this->sql .= implode(null, $args);
			return $this;
		}
		return $this->sql;
	}


	/**
	 *	Optionally set the MySQL connection to use, for `mysql_real_escape_string`, otherwise use 
	 *	MySQL wants \n, \r and \x1a
	 *	Remember to slash underscores (_) and percent signs (%), too, if you're going use the LIKE operator on the variable
	 *	$search = array("\x00", "\x0a", "\x0d", "\x1a", "\x09");
	 *	$replace = array('\0', '\n', '\r', '\Z' , '\t');
	 *	str_replace($search, $replace, $Data )		Taken from: http://php.net/manual/en/function.addslashes.php#56848
	 */
	public static function setConn($conn = null)
	{
		self::$conn = $conn;
		if (self::$conn instanceof \PDO) {
			self::$escaper = [$conn, 'quote'];
		}
		else if (self::$conn instanceof \MySQLi) {
			self::$escaper = [$conn, 'real_escape_string'];
		}
		else {
			self::$escaper = '';
		}
	}

	/**
	 *	Optionally set the MySQL connection to use, for `mysql_real_escape_string`, otherwise use 
	 *	MySQL wants \n, \r and \x1a
	 *	Remember to slash underscores (_) and percent signs (%), too, if you're going use the LIKE operator on the variable
	 *	$search = array("\x00", "\x0a", "\x0d", "\x1a", "\x09");
	 *	$replace = array('\0', '\n', '\r', '\Z' , '\t');
	 *	str_replace($search, $replace, $Data )		Taken from: http://php.net/manual/en/function.addslashes.php#56848
	 */
	public static function setConnection($conn = null)
	{
		self::$conn = $conn;
	}

	/**
	 *	Use for testing purposes only!
	 *	Creates a dummy anonymous `connection` class, which implements real_escape_string() which uses addslashes()
	 */
	public static function setDummyConn()
	{
		//	configure dummy conn for real_escape_string!
		self::$conn = new class { function real_escape_string($str) { return addslashes($str); } };
	}


	//	eg. SQL::LOCK_TABLES('users WRITE', 'worlds READ');
	//	eg. SQL::UNLOCK_TABLES('users, worlds');
	public static function LOCK_TABLES(...$tables)
	{
		return 'LOCK TABLES ' . implode(', ', $tables);
	}
	public static function UNLOCK_TABLES()
	{
		return 'UNLOCK TABLES';
	}


	// MySQL only accepts 3-byte UTF-8! SANITIZE our "UTF-8" string for MySQL!
	// Taken from: http://stackoverflow.com/questions/8491431/remove-4-byte-characters-from-a-utf-8-string
	public static function utf8(string $str)
	{
		return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $str);
	}	// addcslashes with: "\\\000\n\r'\"\032%_"	http://www.aichengxu.com/mysql/3944424.htm ... still doesn't protect against multi-byte attacks ...

	public function e(string $value)													//	pick your poison! e() || esc() || escape()
	{
		if (is_string($value)) {
			$this->sql .= '"' . self::$conn->real_escape_string(self::utf8($value)) . '"';
		}
		else if (is_numeric($value)) {
			$this->sql .= $value;
		}
		else if (is_null($value)) {
			$this->sql .= 'NULL';
		}
		else {
			foreach ($value as $key => &$v)
				$v = $this->sanitize($v);	//	experimental ???
			$this->sql .= '(' . $value . ')';
		}
		return $this;
	}
	public function esc(string $value)
	{
		$this->sql .= '"' . self::$conn->real_escape_string(self::utf8($value)) . '"';
		return $this;
	}
/*
	public function escape(string $value)
	{
		$this->sql .= '"' . self::$conn->real_escape_string(self::utf8($value)) . '"';
		return $this;
	}
*/
	// This function is used in post.php files to remove 4-byte UTF-8 characters (MySQL only accepts upto 3-bytes), pack multiple space values, trim and get only $length characters!
	public static function varchar($str, $length = 65535, $empty = '', $compact = true)
	{
		//return mb_substr(str_squash(preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $str)), 0, $length);
		//return mb_substr(trim(mb_ereg_replace('\s+', ' ', preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $str))), 0, $length);
		$str = trim(mb_substr(trim(mb_ereg_replace($compact ? '\s+' : ' +', ' ', self::utf8($str))), 0, $length)); // 2x trim() because after shortening it, we could have a space at the end of our shortened (substr) string
		return empty($str) ? $empty : $str;
	}


	public function escapeString(string $value)
	{
		return '"' . self::$conn->real_escape_string(self::utf8($value)) . '"';
	}
	public function returnEscaped(string $value)
	{
		return '"' . self::$conn->real_escape_string(self::utf8($value)) . '"';
	}


	/**
	 *	WARNING: this is a Multibyte escaper based on mysqli::real_escape_string();
	 *		typically used with UTF-8 connections and strings!
	 *	As long as you make sure your mb_internal_encoding() is set the same as your database connection;
	 *		This is the same as using mysqli::real_escape_string() when you use the same encoding on both ends, typically UFT-8!
	 *
	 *	Notes:
	 *		PDO::quote sucks!
	 *			http://php.net/manual/en/pdo.quote.php
	 *		It adds a weird syntax (`'` becomes `''`),
	 *			doesn't support NULL and forces "'",
	 *			unlike MySQLi->real_escape_string()
	 *
	 *	Notes on mysqli::real_escape_string
	 *		http://php.net/manual/en/mysqli.real-escape-string.php#46339
	 *		`Note that this function will NOT escape _ (underscore) and % (percent) signs, which have special meanings in LIKE clauses.`
	 *
	 *		`Characters encoded are NUL (ASCII 0), \n, \r, \, ', ", and Control-Z.`	ctl-Z = dec:26 hex:1A
	 *
	 *		`NewLine (\n) is 10 (0xA) and CarriageReturn (\r) is 13 (0xD).`
	 *
	 *	http://php.net/manual/en/function.addslashes.php#33975
	 *		`Note that when using addslashes() on a string that includes cyrillic characters, addslashes() totally mixes up the string, rendering it unusable.`
	 *
	 *	Notes:
	 *		I don't use preg_replace() anymore because of this comment:
	 *		http://php.net/manual/en/function.preg-replace.php#74037
	 *		`Be aware that when using the "/u" modifier, if your input text contains any bad UTF-8 code sequences,
	 *			then preg_replace will return an empty string, regardless of whether there were any matches.`
	 *		`This is due to the PCRE library returning an error code if the string contains bad UTF-8.`
	 */
	public static function escape(string $string)
	{
	//	return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]', '\\\0', $string);	//	includes % and _ because they have special meaning in MySQL LIKE statements!
		return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $string);	//	27 = ' 22 = " 5C = \ 1A = ctl-Z 00 = \0 (NUL) 0A = \n 0D = \r
	//	return preg_replace('~[\x00\x0A\x0D\x1A\x22\x27\x5C]~u', '\\\$0', $string);	preg_replace() equivalent, they differ in their `backreference` syntax!
	}

	/**
	 *	Version suitable for escaping strings for MySQL LIKE statements; which should include the % and _ characters!
	 */
	public static function escapeLIKE(string $string)
	{
		return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]', '\\\0', $string);
	}

	/**
	 *	WARNING: This is NOT the same as PDO::quote ... YET! This is more like mysqli::real_escape_string!
	 *	PDO adds the weird '' syntax to strings, which has been shown to be vulnerable to attack under certain conditions!
	 */
	public static function quote(string $string)
	{
	//	return self::$quot . mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]', '\\\0', $string) . self::$quot;
		return self::$quot . mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $string) . self::$quot;
	//	$this->sql .= '"' . ($escape ? self::$conn->real_escape_string(self::utf8($value)) : $value) . '"';
	//	return $this;
	}

	/**
	 *	Use this function to `clean` up all NON-essential characters!
	 *	\s modifier: " ,\t,\r,\n,\v,\f"		\v = vertical tab (decimal:11)		\f = form feed (decimal:12) = \x0C
	 *
	 */
	/**
	 *	Taken from: http://www.regular-expressions.info/unicode.html
	 *	\p{Z} or \p{Separator}: any kind of whitespace or invisible separator.
	 *		\p{Zs} or \p{Space_Separator}: a whitespace character that is invisible, but does take up space.
	 *		\p{Zl} or \p{Line_Separator}: line separator character U+2028.
	 *		\p{Zp} or \p{Paragraph_Separator}: paragraph separator character U+2029.
	 *	\p{C} or \p{Other}: invisible control characters and unused code points.
	 *		\p{Cc} or \p{Control}: an ASCII or Latin-1 control character: 0x00–0x1F and 0x7F–0x9F.
	 *		\p{Cf} or \p{Format}: invisible formatting indicator.
	 *		\p{Co} or \p{Private_Use}: any code point reserved for private use.
	 *		\p{Cs} or \p{Surrogate}: one half of a surrogate pair in UTF-16 encoding.
	 *		\p{Cn} or \p{Unassigned}: any code point to which no character has been assigned.
	 */
	public static function sanitize(string $str)
	{
		return preg_replace('/(?!\s)\p{C}/u', null, $str);	//	creates a NEGATIVE look-ahead for \s characters; while \p{C} will remove ALL non-essential characters (ie. invisible control characters and unused code points) such as \0

		if (is_numeric($value)) return $value;
		if (is_string($value)) self::$quot . mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]', '\\\0', self::utf8($value)) . self::$quot;
		if (is_null($value)) return 'NULL';
		if (is_bool($value)) return $value ? 'TRUE' : 'FALSE';
		foreach ($value as $key => &$v)
			$v = $this->sanitize($v);
		return '(' . implode(', ', $value) . ')';
	}


	/**
	 *	ArrayAccess interface
	 */
    /**
     * Gets a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object
     *
     * @return mixed The value of the parameter or an object
     *
     * @throws \InvalidArgumentException if the identifier is not defined
     */
	public function offsetGet($sql)
	{
		$this->sql .= is_null($sql) ? 'NULL' : $sql;
		return $this;
	}
    /**
     * Sets a parameter or an object.
     *
     * Objects must be defined as Closures.
     *
     * Allowing any PHP callable leads to difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same name as an existing parameter would break your container).
     *
     * @param string $id    The unique identifier for the parameter or object
     * @param mixed  $value The value of the parameter or a closure to define an object
     *
     * @throws \RuntimeException Prevent override of a frozen service
     */
	public function offsetSet($idx, $sql)
	{
//		if (is_numeric($idx))
//			$this->sql[$idx] .= $sql;
//		else
			$this->sql = $sql;
	}

    /**
     * Checks if a parameter or an object is set.
     *
     * @param string $id The unique identifier for the parameter or object
     *
     * @return bool
     */
	public function offsetExists($idx)
	{
		return isset($this->sql[$idx]);
	}

    /**
     * Unsets a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object
     */
	public function offsetUnset($idx)
	{
		unset($this->sql[$idx]);
	}



    /**
     * REFERENCE
     *
     * Builds a {@see \Closure} capable of instantiating the given $className without
     * invoking its constructor.
     *
     * @param string $className
     *
     * @return Closure
     */

    /**
     * Builds a {@see \Closure} capable of instantiating the given $className without
     * invoking its constructor.
     *
     * @param string $type
     * @param string $func
     *
     * @return void
     */
	public static function createDataType(string $type, callable $func)
	{
		self::$types[$type] = $func;
	}

    /**
     * Builds a {@see \Closure} capable of instantiating the given $className without
     * invoking its constructor.
     *
     * @param string $className
     *
     * @return Closure
     */
	public static function createModifier(string $modifier, callable $func)	//	should add the `position`, like `before`, `after` etc.
	{
		self::$modifiers[$modifier] = $func;
	}



	//	recursively `packs` all string values within an array/value
	//	used on array values for :jsonify / :jsonencode
	public static function pack($value)	//	AKA packStrings ?	/ packAll ?
	{
		if (is_array($value)) {
			foreach($value as $k => &$v) {
				if (is_string($v)) {
					$value[$k] = trim(mb_ereg_replace('\s+', ' ', $v));
				}
				else if (is_array($v)) {	//	2 levels handled internally, just a small optimization for large multi-level arrays!
					foreach($v as $k2 => $v2) {
						if (is_string($v2)) {
							$v[$k2] = trim(mb_ereg_replace('\s+', ' ', $v2));
						}
						else if (is_array($v2)) {
							$v[$k2] = self::pack($v2);
						}
					}
				}
			}
			return $value;
		}
		if (is_string($value)) {
			return trim(mb_ereg_replace('\s+', ' ', $value));
		}
		return $value;
	}
	//	recursively `trims` all string values within an array/value
	//	used on array values for :jsonify / :jsonencode  where :trim
	public static function trim($value)	//	AKA packStrings ?	/ packAll ?
	{
		if (is_array($value)) {
			foreach($value as $k => &$v) {
				if (is_string($v)) {
					$value[$k] = trim(mb_ereg_replace('\s+', ' ', $v));
				}
				else if (is_array($v)) {	//	2 levels handled internally, just a small optimization for large multi-level arrays!
					foreach($v as $k2 => $v2) {
						if (is_string($v2)) {
							$v[$k2] = trim(mb_ereg_replace('\s+', ' ', $v2));
						}
						else if (is_array($v2)) {
							$v[$k2] = self::pack($v2);
						}
					}
				}
			}
			return $value;
		}
		if (is_string($value)) {
			return trim(mb_ereg_replace('\s+', ' ', $value));
		}
		return $value;
	}

	/**
	 *	Supplementary Reference for mb_trim() and mb_pack()
	 *
	 *	Taken from: http://www.regular-expressions.info/unicode.html
	 *
	 *	\p{Z} or \p{Separator}: any kind of whitespace or invisible separator.
	 *		\p{Zs} or \p{Space_Separator}: a whitespace character that is invisible, but does take up space.
	 *		\p{Zl} or \p{Line_Separator}: line separator character U+2028.
	 *		\p{Zp} or \p{Paragraph_Separator}: paragraph separator character U+2029.
	 *
	 *	\p{C} or \p{Other}: invisible control characters and unused code points.
	 *		\p{Cc} or \p{Control}: an ASCII or Latin-1 control character: 0x00–0x1F and 0x7F–0x9F.
	 *		\p{Cf} or \p{Format}: invisible formatting indicator.
	 *		\p{Co} or \p{Private_Use}: any code point reserved for private use.
	 *		\p{Cs} or \p{Surrogate}: one half of a surrogate pair in UTF-16 encoding.
	 *		\p{Cn} or \p{Unassigned}: any code point to which no character has been assigned.
	 *
	 *	\s modifier: " ,\t,\r,\n,\v,\f"		\v = vertical tab (decimal:11)		\f = form feed (decimal:12) = \x0C
	 *
	 *	trim:	" ,\t,\r,\n,\0,\x0B"	\x0B == \v == vertical tab (decimal:11)		\n = line feed		\t = tab		\r = carriage return
	 *
	 *	ctrl-Z = decimal:26	symbol: ^Z  / SUB  / EOF
	 *
	 *	`Control character` class:		https://en.wikipedia.org/wiki/Control_character
	 *
	 *	`\s` includes characters in two different classes
	 *		\t, \r, \n are defined in the \p{Cc} class
	 *		` ` (space) is defined in the \p{Zs} class
	 *
	 *	Supplementary Information from:
	 *		http://nadeausoftware.com/articles/2007/9/php_tip_how_strip_punctuation_characters_web_page#Removinglineparagraphandwordseparators
	 *
	 *  \p{Z}  -   `Removing line, paragraph, and word separators`
	 *             `Separator characters delimit lines, paragraphs, and words.
	 *              The most common separator is a space character, but Unicode defines 18 different spaces,
	 *              such as n- and m-sized spaces, and a non-breaking space.
	 *              Replace all of these with a generic space to simplify content analysis and further regular expressions.`
	 *
	 *		// Remove control, formatting, and surrogate characters
	 *		$text = preg_replace( '/[\p{Cc}\p{Cf}\p{Cs}]/u', ' ', $text );
	 *
	 */
	/**
	 *	Advanced UTF-8 enabled `trimmer`
	 *
	 *	Removes all `invisible control characters and unused code points.`
	 *		as well as `any kind of whitespace or invisible separator.`
	 *
	 *	My `exact` match for trim()'s characters would be: [ \x00\x09\x0A\x0B\x0D]	\0=00  \t=09 \n=0A \v=0B \r=0D
	 *
	 *	However, I find this range to be too limited when dealing with UTF-8 data from external sources.
	 *
	 * @link http://news.php.net/php.internals/74654
	 *
	 *
	 */
	public static function mb_trim(string $str)
	{
		return preg_replace('/^[\p{Z}\p{C}]+|[\p{Z}\p{C}]+$/u', null, $str);
	}


	/**
	 *	Advanced UTF-8 enabled string packer (whitespace merger/normalizer)
	 *
	 *	Step 1:	'/(?!\s)\p{C}/u'
	 *		Remove `invisible control characters and unused code points`.
	 *			Except those defined by the \s class, which include \t\r\n
	 *			In other words, remove all `control characters` except \s
	 *			The negative look-ahead class `(?!\s)` removes the \s characters
	 *				from the next pattern, which is `\p{C}`
	 *			We do this to preserve the \s range, but remove characters like \0
	 *
	 *	Step 2:	'/^[\p{Z}\s]+|[\p{Z}\s]+$/u'
	 *		`trim`
	 *			Remove leading and trailing whitespace and control characters.
	 *
	 *	Step 3:	'/[\p{Z}\s]+/u'
	 *		\p{Z} - `any kind of whitespace or invisible separator.`
	 *			\p{Z} includes ` ` (space), but not \t\r\n, which we include with \s
	 *			Replace all `\s+` ranges with ' '
	 *			`\s` does NOT include the additional whitespace characters as defined by \p{Z},
	 *				both \s and \p{Z} include ` `,
	 *				but \s includes \t\r\n (control characters),
	 *				and \p{Z} includes all other whitespace like &nbsp; / &#160;
	 *
	 *	About the whitepace merging enhancement: '/(?! )[\p{Z}\s]+/u' (the middle pattern)
	 *		Previously, I would have just used '/\s+/u', ' ' to merge whitespace.
	 *			However, I found that adding the negative lookahead `(?! )` for the space character
	 *			to improve performace in ALL cases (large and small text) by 20%-40%
	 *		This essentially increases performance on pre-formatted text that only uses traditional spaces,
	 *			because it essentially will leave those strings alone; ie. not having to do any work on them!
	 *			Even a string with 5 spaces in 27 characters was 25% faster to process!
	 *		This was an enhancement I developed myself on 21 July 2017 @ 9pm, the day after Chester Bennington died!
	 */
	public static function mb_pack(string $str)
	{
		static $patterns     =	null;
		static $replacements =	null;
		if ($patterns === null) {	//	`caching` the arrays construction in-case mb_pack() is called again!
			$patterns        =	['/(?!\s)\p{C}/u', '/^[\p{Z}\s]+|[\p{Z}\s]+$/u', '/(?! )[\p{Z}\s]+/u'];
			$replacements    =	[null, null, ' '];	//	null == ''
		}
		return preg_replace($patterns, $replacements, $str);
	}
}

/**
 *	Helper function to build a new SQL query object, just saves using `new` :p
 */
function SQL(string $stmt = null, ...$params)
{
	return new SQL($stmt, ...$params);
}
