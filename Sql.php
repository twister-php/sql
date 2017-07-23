<?php

/**
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

namespace Twister;

/**
 *	Raw SQL Query String Builder
 *
 *	@author      Trevor Herselman <therselman@gmail.com>
 *	@copyright   Copyright (c) 2017 Trevor Herselman
 *	@license     http://opensource.org/licenses/MIT
 *	@link        https://github.com/twister-php/sql
 *	@api
 */
class Sql implements \ArrayAccess
{
    /**
     *	The magic starts here!
     *
     *	@var string|null
     */
	protected $sql				=	null;

    /**
     *	Quote style to use for strings.
     *	Can be either `'"'` or `"'"`
     *
     *	@var string
     */
	protected static $quot		=	'"';

    /**
     *	@var callable[]|null
     */
	protected static $modifiers	=	null;

    /**
     *	@var callable[]|null
     */
	protected static $types		=	null;

	/**
     * 	@var string[] translations
	 */
	protected static $translations	=	[	'EXPLAIN'		=>	'EXPLAIN ',
											'SELECT'		=>	'SELECT ',
											'DELETE'		=>	'DELETE ',
											'INSERT'		=>	'INSERT ',
											'UPDATE'		=>	'UPDATE ',
											'CALL'			=>	'CALL ',

											'INSERT_INTO'	=>	'INSERT INTO ',
											'INSERTINTO'	=>	'INSERT INTO ',
											'DELETE_FROM'	=>	'DELETE FROM ',
											'DELETEFROM'	=>	'DELETE FROM ',

											'SELECT_ALL'	=>	'SELECT *',
											'SA'			=>	'SELECT *',
											'SALL'			=>	'SELECT *',
											'S_ALL'			=>	'SELECT *',

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
											'ALTER_DATABASE'=>	'ALTER DATABASE ',
											'ALTER_SCHEMA'	=>	'ALTER SCHEMA ',
											'ALTER_EVENT'	=>	'ALTER EVENT ',
											'ALTER_FUNCTION'=>	'ALTER FUNCTION ',
											'DATABASE'		=>	'DATABASE ',
											'SCHEMA'		=>	'SCHEMA ',
											'EVENT'			=>	'EVENT ',
											'FUNCTION'		=>	'FUNCTION ',
											'TABLE'			=>	'TABLE ',

											'ALL'			=>	'*',
											'DISTINCT'		=>	'DISTINCT ',
											'DISTINCTROW'	=>	'DISTINCTROW ',
											'HIGH_PRIORITY'	=>	'HIGH_PRIORITY ',
											'HIGH'			=>	'HIGH_PRIORITY ',
											'STRAIGHT_JOIN'	=>	'STRAIGHT_JOIN ',
											'SQL_SMALL_RESULT'=>'SQL_SMALL_RESULT ',
											'SMALL'			=>	'SQL_SMALL_RESULT ',
											'SQL_BIG_RESULT'=>	'SQL_BIG_RESULT ',
											'BIG'			=>	'SQL_BIG_RESULT ',
											'SQL_BUFFER_RESULT'=>'SQL_BUFFER_RESULT ',
											'BUFFER'		=>	'SQL_BUFFER_RESULT ',
											'SQL_CACHE'		=>	'SQL_CACHE ',
											'CACHE'			=>	'SQL_CACHE ',
											'SQL_NO_CACHE'	=>	'SQL_NO_CACHE ',
											'NO_CACHE'		=>	'SQL_NO_CACHE ',
											'SQL_CALC_FOUND_ROWS'=>	'SQL_CALC_FOUND_ROWS ',
											'CALC'			=>	'SQL_CALC_FOUND_ROWS ',

											'DELAYED'		=>	'DELAYED ',

											'LOW_PRIORITY'	=>	'LOW_PRIORITY ',
											'LOW'			=>	'LOW_PRIORITY ',
											'QUICK'			=>	'QUICK ',
											'IGNORE'		=>	'IGNORE ',

											'TRUNCATE'		=>	'TRUNCATE ',
											'TRUNCATE_TABLE'=>	'TRUNCATE TABLE ',
											'TT'			=>	'TRUNCATE TABLE ',

											'CA'			=>	'COUNT(*)',
											'CAA'			=>	'COUNT(*) AS ',
											'CA_AS'			=>	'COUNT(*) AS ',
											'COUNT_ALL'		=>	'COUNT(*)',
											'COUNT_ALL_AS'	=>	'COUNT(*) AS ',
											'COUNT'			=>	'COUNT',
											'LAST_INSERT_ID'=>	'LAST_INSERT_ID()',
											'ROW_COUNT'		=>	'ROW_COUNT()',
											'A'				=>	'*',
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
											'J'				=>	PHP_EOL . "\tJOIN"             . PHP_EOL . "\t\t",
											'IJ'			=>	PHP_EOL . "\tINNER JOIN"       . PHP_EOL . "\t\t",
											'LJ'			=>	PHP_EOL . "\tLEFT JOIN"        . PHP_EOL . "\t\t",
											'LOJ'			=>	PHP_EOL . "\tLEFT OUTER JOIN"  . PHP_EOL . "\t\t",
											'RJ'			=>	PHP_EOL . "\tRIGHT JOIN"       . PHP_EOL . "\t\t",
											'ROJ'			=>	PHP_EOL . "\tRIGHT OUTER JOIN" . PHP_EOL . "\t\t",
											'OJ'			=>	PHP_EOL . "\tOUTER JOIN"       . PHP_EOL . "\t\t",
											'CJ'			=>	PHP_EOL . "\tCROSS JOIN"       . PHP_EOL . "\t\t",
											'SJ'			=>	PHP_EOL . "\tSTRAIGHT_JOIN"    . PHP_EOL . "\t\t",
											'NJ'			=>	PHP_EOL . "\tNATURAL JOIN"     . PHP_EOL . "\t\t",
											'W'				=>	PHP_EOL . 'WHERE'              . PHP_EOL . "\t",
											'G'				=>	PHP_EOL . 'GROUP BY ',
											'H'				=>	PHP_EOL . 'HAVING ',
											'O'				=>	PHP_EOL . 'ORDER BY ',
											'OB'			=>	PHP_EOL . 'ORDER BY ',
											'L'				=>	PHP_EOL . 'LIMIT ',

											'USING'			=>	' USING ',
											'USE'			=>	' USE ',
											'IGNORE'		=>	' IGNORE ',
											'FORCE'			=>	' FORCE ',
											'NATURAL'		=>	' NATURAL ',

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
											'NULL'			=>	'NULL',				//	Warning: don't add spaces here, used in several places without spaces!
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
											'CHARACTER_SET'	=>	' CHARACTER SET ',
											'CHARACTER'		=>	' CHARACTER ',
											'INTO_DUMPFILE'	=>	' INTO DUMPFILE ',
											'DUMPFILE'		=>	'DUMPFILE ',
											'OUTFILE'		=>	'OUTFILE ',

											'INTO'			=>	'INTO ',
											'OFFSET'		=>	' OFFSET ',

											'FOR_UPDATE'					=>	PHP_EOL . 'FOR UPDATE',
											'LOCK_IN_SHARE_MODE'			=>	' LOCK IN SHARE MODE',
											'FOR_UPDATE_LOCK_IN_SHARE_MODE'	=>	PHP_EOL . 'FOR UPDATE LOCK IN SHARE MODE',

											'ON_DUPLICATE_KEY_UPDATE'		=>	PHP_EOL . 'ON DUPLICATE KEY UPDATE' . PHP_EOL . "\t",

											'AUTO_INCREMENT'=>	' AUTO_INCREMENT',
											'INT'			=>	' INT',
											'PK'			=>	'PRIMARY KEY ',
											'PRIMARY_KEY'	=>	'PRIMARY KEY ',
											'UNIQUE_KEY'	=>	'UNIQUE KEY ',
											'ENGINE'		=>	PHP_EOL . 'ENGINE',

											'IF'			=>	' IF ',
											'SET'			=>	' SET ',

											'COMMA'			=>	', ',
											'C'				=>	', ',

											'_'				=>	' ',
											'__'			=>	', ',
											'Q'				=>	'"',
											'SPACE'			=>	' ',
											'SP'			=>	' ',
											'_O'			=>	'(',
											'C_'			=>	')',
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
											'AS'			=>	' AS ',			//	had to make changes here!
										//	'AS_'			=>	'AS ',
										//	'_AS'			=>	' AS',
										//	'_AS_'			=>	' AS ',
											'ON'			=>	' ON ',			//	had to make changes here!
										//	'ON_'			=>	'ON ',
										//	'_ON'			=>	' ON',
										//	'_ON_'			=>	' ON ',
											'AND'			=>	' AND ',		//	had to make changes here!
										//	'AND_'			=>	'AND ',
										//	'_AND'			=>	' AND',
										//	'_AND_'			=>	' AND ',
											'OR'			=>	' OR ',		//	had to make changes here!
										//	'OR_'			=>	'OR ',
										//	'_OR'			=>	' OR',
										//	'_OR_'			=>	' OR ',
											'XOR'			=>	' XOR ',
										//	'XOR_'			=>	'XOR ',
										//	'_XOR'			=>	' XOR',
										//	'_XOR_'			=>	' XOR ',
											'ADD'			=>	'+',
											'ADD_'			=>	'+ ',
											'_ADD'			=>	' +',
											'_ADD_'			=>	' + ',
											'SUB'			=>	'-',
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

											'MATCH'			=>	'MATCH',
											'MATCH_'		=>	'MATCH ',
											'_MATCH'		=>	' MATCH',
											'_MATCH_'		=>	' MATCH ',

											'AFTER'			=>	'AFTER',
											'AFTER_'		=>	'AFTER ',
											'_AFTER'		=>	' AFTER',
											'_AFTER_'		=>	' AFTER ',

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
											'_BETWEEN_'		=>	' BETWEEN ',

											'OUT'			=>	'OUT ',
											'_OUT_'			=>	' OUT ',
											'INOUT'			=>	'INOUT ',
											'_INOUT_'		=>	' INOUT ',

											'PARTITION'		=>	PHP_EOL . 'PARTITION ',
											'WITH_ROLLUP'	=>	' WITH ROLLUP ',
											'DEFAULT'		=>	' DEFAULT ',
										];



	/**
	 *	Construct a new SQL statement, initialized by the optional $stmt string
	 *		and an optional list of associated $params
	 *
	 *	Can be full or partial queries, fragments or statements
	 *
	 *	No syntax checking is done
	 *
	 *	The object can be initialized in multiple ways:
	 *		but operates similar to `sprintf()` and `PDO::prepare()`
	 *
	 *	The object can be initialized in multiple ways:
	 *		but operates very much like `sprintf()` or `PDO::prepare()`
	 *
	 *	Basic examples:
	 *
	 *		`$sql = sql();`
	 *		`$sql = sql('@', $raw);`                        //	@ = raw output - no escaping or quotes
	 *		`$sql = sql('?', $mixed_auto_escaped);`         //	? = (only) strings are escaped & quoted
	 *		`$sql = sql('Hello @', 'World');`               //	'Hello World'
	 *		`$sql = sql('Hello ?', 'World');`               //	'Hello "World"'
	 *		`$sql = sql('age >= @', 18);`                   //	age >= 18
	 *		`$sql = sql('age >= ?', 18);`                   //	age >= 18
	 *		`$sql = sql('age >= ?', '18');`                 //	age >= 18  (is_numeric('18') === true)
	 *		`$sql = sql('age IS ?', null);`                 //	age IS NULL
	 *		`$sql = sql('SELECT * FROM users');`
	 *		`$sql = sql('SELECT * FROM users WHERE id = ?', $id);`
	 *		`$sql = sql('SELECT @', 'CURDATE()');`          //	SELECT CURDATE()    - @ = raw output
	 *		`$sql = sql('SELECT ?', 'CURDATE()');`          //	SELECT "CURDATE()"	- ? = escaped & incorrect
	 *
	 *	Examples with output:
	 *
	 *		`$sql = sql();`
	 *		`echo $sql;`                            //	`sql()` returns nothing until given commands
	 *		``
	 *
	 *		`echo sql();`                           //	`sql()` starts as an empty string
	 *		``
	 *
	 *		`echo sql('Hello @', 'World');`         //	@ = raw value, no escapes or quotes
	 *		`Hello World`
	 *
	 *		`echo sql('Hello ?', 'World\'s');`      //	? = escaped and quoted
	 *		or
	 *		`echo sql('Hello ?', "World's");`       //	? = escaped and quoted
	 *		`Hello "World\'s"`
	 *
	 *		`echo sql('age >= @', 18);`             //	@ = raw value, no escapes or quotes
	 *		`age >= 18`
	 *
	 *		`echo sql('age >= ?', 18);`             //	is_numeric(18) === true
	 *		`age >= 18`
	 *
	 *		`echo sql('age >= ?', '18');`           //	is_numeric('18') === true
	 *		`age >= 18`
	 *
	 *		`echo sql('name IS @', null);`          //	@ null = ''
	 *		`name IS `
	 *
	 *		`echo sql('name IS ?', null);`          //	? null = 'NULL'
	 *		`name IS NULL `
	 *
	 *		`echo sql('dated = @', 'CURDATE()');`   //	@ = raw value, no escapes or quotes
	 *		`date = CURDATE()`
	 *
	 *		`echo sql('dated = ?', 'CURDATE()');`   //	? = escaped and quoted
	 *		`date = "CURDATE()"`
	 *
	 *		`echo sql('SELECT * FROM users');`
	 *		`SELECT * FROM users`
	 *
	 *		`$id = 5;`
	 *		`echo sql('SELECT * FROM users WHERE id = ?', $id);`
	 *		`SELECT * FROM users WHERE id = 5`
	 *
	 *		`$name = "Trevor's Revenge";`
	 *		`echo sql('SELECT * FROM users WHERE name = ?', $name);`
	 *		`SELECT * FROM users WHERE name = "Trevor\'s Revenge"`	//	UTF-8 aware escapes
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *                      {@see self::prepare()} for syntax rules
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return void
	 */
	public function __construct($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql = $stmt;
		}
		else {
			$this->prepare($stmt, ...$params);
		}
	}

	/**
	 *	__toString() Magic Method
	 *
	 *	{@link http://php.net/manual/en/language.oop5.magic.php#object.tostring}
	 *
	 *	@return	string $this->sql
	 */
	public function __toString()
	{
		return $this->sql;
	}

	/**
	 *	__invoke() Magic Method
	 *
	 *	@alias prepare()
	 *
	 *	See {@see prepare()} for optional syntax rules
	 *
	 *	{@link http://php.net/manual/en/language.oop5.magic.php#object.invoke}
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function __invoke($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= is_null($sql) ? self::$translations['NULL'] : $sql;
			return $this;
		}
		return $this->prepare($stmt, ...$params);
	}


	/**
	 *	CALL Stored Procudure
	 *
	 *	This function has the ability to auto-detect if you've
	 *		pre-prepared the format for individual values;
	 *		eg. call('sp_name(?, ?, @)', $v1, $v2, $v3)
	 *		vs. call('sp_name', $v1, $v2, $v3)
	 *
	 *	The function can automatically generate the required parameter list for you!
	 *		This is useful if you don't have any special string handling requirements
	 *
	 *	To disable value escaping, use one of the following techniques:
	 *			->call('sp_name(LAST_INSERT_ID(), @, @, ?)', 'u.name', '@sql_variable', $name)
	 *			->call('sp_name', ['@' => 'LAST_INSERT_ID()'])
	 *			->call('sp_name(@, ?)', 'LAST_INSERT_ID()', $name)
	 *			->call('SELECT sp_name(@, ?)', 'LAST_INSERT_ID()', $name)
	 *			->call('SELECT sp_name(LAST_INSERT_ID(), ?)', $name)
	 *
	 *	Docs:
	 *		PDO:        {@link http://php.net/manual/en/pdo.prepared-statements.php}
	 *		MySQL:      {@link https://dev.mysql.com/doc/refman/5.7/en/call.html}
	 *		PostgreSQL: {@link https://www.postgresql.org/docs/9.1/static/sql-syntax-calling-funcs.html}
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
	 *	@todo Possibly detect the connection type; and use the appropriate syntax; because PostgreSQL uses `SELECT sp_name(...)`
	 *
	 *	@param  string $sp_name Stored procedure name, or pre-prepared string
	 *
	 *	@param  mixed  ...$params  Parameters required for the stored procedure
	 *
	 *	@return $this
	 */
	public function call($sp_name = null, ...$params)
	{
		if (strpos($sp_name, '(') === false) {	//	auto-detect if user pre-prepared their format/pattern eg. CALL('sp_name(?, ?, @)', ...)
			return $this->prepare('CALL ' . $sp_name, ...$params);
		}
		return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
	}

	/**
	 *	CALL Stored Procudure - shorthand for `call()`
	 *
	 *	@alias call()
	 *
	 *	@param  string $sp_name Stored procedure name, or pre-prepared string
	 *
	 *	@param  mixed  ...$params  Parameters required for the stored procedure
	 *
	 *	@return	$this
	 */
	public function c($sp_name = null, ...$params)
	{
		if (strpos($sp_name, '(') === false) {
			return $this->prepare('CALL ' . $sp_name, ...$params);
		}
		return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
	}

	/**
	 *	CALL Stored Procudure - shorthand for `call()`
	 *
	 *	@alias call()
	 *
	 *	@param  string $sp_name Stored procedure name, or pre-prepared string
	 *
	 *	@param  mixed  ...$params  Parameters required for the stored procedure
	 *
	 *	@return	$this
	 */
	public function sp($sp_name = null, ...$params)
	{
		if (strpos($sp_name, '(') === false) {
			return $this->prepare('CALL ' . $sp_name, ...$params);
		}
		return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
	}

	/**
	 *	CALL Stored Procudure - shorthand for `call()`
	 *
	 *	@alias call()
	 *
	 *	@param  string $sp_name Stored procedure name, or pre-prepared string
	 *
	 *	@param  mixed  ...$params  Parameters required for the stored procedure
	 *
	 *	@return	$this
	 */
	public function storedProc($sp_name = null, ...$params)
	{
		if (strpos($sp_name, '(') === false) {
			return $this->prepare('CALL ' . $sp_name, ...$params);
		}
		return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
	}


	/**
	 *	Generates an SQL `INSERT` statement
	 *
	 *	See {@see prepare()} for optional syntax rules
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function insert($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['INSERT'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['INSERT'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `INSERT` statement - shorthand for `insert()`
	 *
	 *	@alias insert()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function i($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['INSERT'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['INSERT'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `INSERT INTO` statement
	 *
	 *	See {@see insertInto()} for alternative spelling
	 *
	 *	See {@see prepare()} for optional syntax rules
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function insert_into($stmt, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['INSERT_INTO'] . $stmt;
			return $this;
		}
		$this->sql .= self::$translations['INSERT'];
		return $this->into($stmt, ...$params);
	}

	/**
	 *	Generates an SQL `INSERT INTO` statement
	 *
	 *	See {@see insert_into()} for alternative spelling
	 *
	 *	See {@see prepare()} for optional syntax rules
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function insertInto($stmt, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'INSERT INTO ' . $stmt;
			return $this;
		}
		$this->sql .= 'INSERT ';
		return $this->into($stmt, ...$params);
	}

	/**
	 *	Generates an SQL `INSERT INTO` statement - shorthand for `insertInto()`
	 *
	 *	@alias insert_into()
	 *	@alias insertInto()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function ii($stmt, ...$params)
	{
		if (empty($params)) {
			$this->sql .= 'INSERT INTO ' . $stmt;
			return $this;
		}
		$this->sql .= 'INSERT ';
		return $this->into($stmt, ...$params);
	}


	/**
	 *	Generates an SQL `INTO` statement
	 *
	 *	A variety of method calling techniques are provided:
	 *
	 *	1) 
	 *
	 *	@todo: complete this documentation
	 *
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
	 *	@param  string    $stmt   Table name or `prepare` style statement
	 *	@param  mixed  ...$params Parameters to use, either columns only or column-value pairs
	 *	@return $this
	 */
	public function into($stmt = null, ...$params)
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
	 *	Generates an SQL `VALUES` statement
	 *
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
	 *
	 *	ANY $key/$index value starting with '@' will cause the value to NOT be escaped!
	 *	eg. VALUES(['value1', '@' => 'UNIX_TIMESTAMP()', '@1' => 'MAX(table)', '@2' => 'DEFAULT', '@3' => 'NULL'])
	 *	eg. VALUES('?, @, @', 'value1', 'DEFAULT', 'NULL')
	 *	eg. VALUES('5, 6, 7, 8, @id, CURDATE()')
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function values($stmt = null, ...$params)
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
	 *	Generates an SQL `VALUES` statement - shorthand for `values()`
	 *
	 *	@alias values()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function v($stmt = null, ...$params)
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
	 *	Generates an SQL `SET` statement
	 *
	 *	@todo fix up this documentation
	 *
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *	https://dev.mysql.com/doc/refman/5.7/en/update.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name SET col_name={expr | DEFAULT}, ... [ ON DUPLICATE KEY UPDATE col_name=expr [, col_name=expr] ... ]
	 *		UPDATE [LOW_PRIORITY] [IGNORE] table_reference SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}]
	 *
	 *		 ... ${id} || $id (looks too much like a variable!  #{id}  :{id}   @user   (entity framework!)  {0} = parameters by index!
	 *
	 *	Alternative 1: (['col1' => $value1, 'col2' => $value2, '@dated' => 'CURDATE()']) 		single array:		[columns => values]
	 *	Alternative 2: (['col1', 'col2', '@dated'], [$value1, $value2, 'CURDATE()'])			two arrays:			[columns], [values]
	 *	Alternative 3: ('col1 = ?, col2 = ?, dated = @', $value1, $value2, 'CURDATE()')
	 *	Alternative 4: (['col1 = ?', col2 = ?, dated = @', $value1, $value2, 'CURDATE()') 	single array v2:	['column', $value, 'column', $value]
	 *
	 *	@param  mixed       ...$args
	 *
	 *	@return	$this
	 */
	public function set(...$args)
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


	/**
	 *	Generates an SQL `EXPLAIN` statement
	 *
	 *	Might be MySQL specific
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function explain($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql = self::$translations['EXPLAIN'] . $this->sql;
			return $this;
		}
		return $this->prepare(self::$translations['EXPLAIN'] . $stmt, ...$params);
	}


	/**
	 *	Generates an SQL 'SELECT' statement
	 *
	 *	This function will join/implode a list of columns/fields
	 *
	 *	eg. `$sql = sql()->select('u.id', 'u.name', 'u.foo', 'u.bar');
	 *
	 *	Due to the greater convenience provided by this method,
	 *		the `prepare()` syntax is not provided here
	 *
	 *	`prepare()`/`sprintf()` like functionality can be provided by using
	 *		another Sql Query Object's constructor like this:
	 *
	 *	`$sql = sql()->select('u.id',
	 *	                      // note how the next `sql()` call will be converted to a string
	 *	                      sql('(SELECT ... WHERE a.id = @) AS foo', $id),
	 *	                      'u.name')
	 *	             ->from('users u');`
	 *
	 *	@param  string ...$cols Column list will be imploded with ', '
	 *
	 *	@return $this
	 */
	public function select(...$cols)
	{
		$this->sql .= self::$translations['SELECT'] . implode(', ', $cols);
		return $this;
	}



	/**
	 *	Generates an SQL `FROM` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function from($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['FROM'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['FROM'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `FROM` statement - shorthand for `from()`
	 *
	 *	@alias from()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function f($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['FROM'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['FROM'] . $stmt, ...$params);
	}


	/**
	 *	Generates an SQL `JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `JOIN` statement - shorthand for `join()`
	 *
	 *	@alias join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function j($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['JOIN'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `JOIN $table ON` statement
	 *
	 *	Combines functionality of JOIN and ON ... experimental!
	 *
	 *	@param  string      $table
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function join_on($table, $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['JOIN'] . $table . self::$translations['ON'];
			return $this;
		}
		return $this->prepare(self::$translations['JOIN'] . $table . self::$translations['ON'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `JOIN $table ON` statement - shorthand for `join_on()`
	 *
	 *	@alias join_on()
	 *
	 *	@param  string      $table
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function j_on($table, $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['JOIN'] . $table . self::$translations['ON'];
			return $this;
		}
		return $this->prepare(self::$translations['JOIN'] . $table . self::$translations['ON'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `JOIN $table ON` statement
	 *
	 *	Alternative spelling for `join_on`
	 *
	 *	@param  string      $table
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function joinOn($table, $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['JOIN'] . $table . self::$translations['ON'];
			return $this;
		}
		return $this->prepare(self::$translations['JOIN'] . $table . self::$translations['ON'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `JOIN $table ON` statement - shorthand for `joinOn()`
	 *
	 *	@alias joinOn()
	 *
	 *	@param  string      $table
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function jOn($table, $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['JOIN'] . $table . self::$translations['ON'];
			return $this;
		}
		return $this->prepare(self::$translations['JOIN'] . $table . self::$translations['ON'] . $stmt, ...$params);
	}


	/**
	 *	Generates an SQL `LEFT JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function left_join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `LEFT JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function leftJoin($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `LEFT JOIN` statement
	 *
	 *	@alias left_join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function lj($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_JOIN'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `LEFT OUTER JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function left_outer_join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_OUTER_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `LEFT OUTER JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function leftOuterJoin($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_OUTER_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `LEFT OUTER JOIN` statement
	 *
	 *	@alias left_outer_join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function loj($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['LEFT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['LEFT_OUTER_JOIN'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `RIGHT JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function right_join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `RIGHT JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function rightJoin($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `RIGHT JOIN` statement
	 *
	 *	@alias right_join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function rj($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_JOIN'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `RIGHT OUTER JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function right_outer_join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_OUTER_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `RIGHT OUTER JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function rightOuterJoin($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_OUTER_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `RIGHT OUTER JOIN` statement
	 *
	 *	@alias right_outer_join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function roj($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['RIGHT_OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['RIGHT_OUTER_JOIN'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `INNER JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function inner_join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['INNER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['INNER_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `INNER JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function innerJoin($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['INNER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['INNER_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `INNER JOIN` statement
	 *
	 *	@alias inner_join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function ij($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['INNER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['INNER_JOIN'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `OUTER JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function outer_join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['OUTER_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `OUTER JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function outerJoin($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['OUTER_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `OUTER JOIN` statement
	 *
	 *	@alias outer_join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function oj($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['OUTER_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['OUTER_JOIN'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `CROSS JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function cross_join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['CROSS_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['CROSS_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `CROSS JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function crossJoin($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['CROSS_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['CROSS_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `CROSS JOIN` statement
	 *
	 *	@alias cross_join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function cj($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['CROSS_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['CROSS_JOIN'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `STRAIGHT_JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function straight_join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['STRAIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['STRAIGHT_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `STRAIGHT_JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function straightJoin($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['STRAIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['STRAIGHT_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `STRAIGHT_JOIN` statement
	 *
	 *	@alias straight_join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function sj($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['STRAIGHT_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['STRAIGHT_JOIN'] . $stmt, ...$params);
	}

	/**
	 *	Generates an SQL `NATURAL JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function natural_join($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['NATURAL_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['NATURAL_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `NATURAL JOIN` statement
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function naturalJoin($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['NATURAL_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['NATURAL_JOIN'] . $stmt, ...$params);
	}
	/**
	 *	Generates an SQL `NATURAL JOIN` statement
	 *
	 *	@alias natural_join()
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function nj($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['NATURAL_JOIN'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['NATURAL_JOIN'] . $stmt, ...$params);
	}



	/**
	 *	Generates an SQL `USING` statement
	 *
	 *	$fields list is joined/imploded with `', '`
	 *	$fields are NOT escaped or quoted
	 *
	 *	Example:
	 *
	 *		echo sql()->using('id', 'acc');
	 *		 USING (id, acc)
	 *
	 *	@param  string ...$fields
	 *
	 *	@return	$this
	 */
	public function using(...$fields)
	{
		$this->sql .= self::$translations['USING'] . '(' . implode(', ', $fields) . ')';
		return $this;
	}

	/**
	 *	Generates an SQL `ON` statement
	 *
	 *	Generates an `ON` statement with convenient `prepare()` syntax (optional)
	 *
	 *	See {@see \Sql::prepare()} for optional syntax rules
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function on($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['ON'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['ON'] . $stmt, ...$params);
	}




	/**
	 *	Generates an SQL `WHERE` statement
	 *
	 *	Generates a `WHERE` statement with convenient `prepare()` syntax (optional)
	 *
	 *	See {@see \Sql::prepare()} for optional syntax rules
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function where($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['WHERE'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['WHERE'] . $stmt, ...$params);
	}

	/**
	 *	Generate an SQL `WHERE` statement - shorthand for `where()`
	 *
	 *	Generate a `WHERE` statement with convenient `prepare()` syntax (optional)
	 *
	 *	This is the same as `where()`, only shorthand form
	 *
	 *	@alias where()
	 *
	 *	See {@see \Sql::prepare()} for optional syntax rules
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       $params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function w($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['WHERE'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['WHERE'] . $stmt, ...$params);
	}


	/**
	 *	Generates an SQL `IN` statement
	 *
	 *	Automatically determines $args member data types
	 *	Automatically quotes and escapes strings
	 *
	 *	Essentially provides the same service as implode()
	 *	But with the added benefit of intelligent escaping and quoting
	 *	However, implode() will be more efficient for numeric arrays
	 *
	 *	Example:
	 *
	 *		`echo sql()->in($array)`
	 *		` IN (0, 1, 2, 3, ...)`
	 *
	 *
	 *	Samples:
	 *		DELETE FROM t WHERE i IN(1,2);
	 *
	 *	@param  mixed       ...$args
	 *
	 *	@return	$this
	 */
	public function in(...$args)
	{
		$comma = null;
		$this->sql .= ' IN (';
		foreach ($args as $arg)
		{
			if (is_numeric($arg)) {
				$this->sql .= $comma . $arg;
			}
			else if (is_string($arg)) {
				$this->sql .= $comma . self::quote($arg);
			}
			else if (is_null($arg)) {
				$this->sql .= $comma . 'NULL';
			}
			else if (is_bool($arg)) {
				$this->sql .= $comma . $arg ? '1' : '0';
			}
			$comma = ', ';
		}
		$this->sql .= ')';
		return $this;
	}


	/**
	 *	Clamp values between a $min and $max range
	 *
	 *	$value can also be a database field name
	 *	All values are rendered without quotes or escapes
	 *	$min and $max can be database field names
	 *
	 *	Example:
	 *		->clamp('price', $min, $max)
	 *
	 *	Samples:
	 *		max($min, min($max, $current));
	 *
	 *	@param  int|string  $value  Value, column or field name
	 *	@param  int|string  $min    Min value or field name
	 *	@param  int|string  $max    Max value or field name
	 *	@param  string|null $as     Optionally print an `AS` clause
	 *
	 *	@return	$this
	 */
	public function clamp($value, $min, $max, $as = null)
	{
		$this->sql .= 'MIN(MAX(' . $value . ', ' . $min . '), ' . $max . ')' . ($as === null ? null : ' AS ' . $as);
		return $this;
	}


	/**
	 *	Generates an SQL `UNION` statement
	 *
	 *	Generates a `WHERE` statement with convenient `prepare()` syntax (optional)
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
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement to `prepare()`;
	 *
	 *	@param  mixed       ...$params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return	$this
	 */
	public function union($stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql .= self::$translations['UNION'] . $stmt;
			return $this;
		}
		return $this->prepare(self::$translations['UNION'] . $stmt, ...$params);
	}


	/**
	 *	Generates an SQL `ORDER BY` statement
	 *
	 *	Example:
	 *
	 *		`echo sql()->order_by('dated DESC', 'name')`
	 *		`ORDER BY (dated DESC, name)`
	 *
	 *	@param  string       ...$cols
	 *
	 *	@return	$this
	 */
	public function order_by(...$cols)
	{
		$this->sql .= self::$translations['ORDER_BY'];
		$comma = null;
		foreach ($cols as $arg)
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
	 *	Generates an SQL `ORDER BY` statement
	 *
	 *	@alias
	 *
	 *	Example:
	 *
	 *		`echo sql()->orderBy('dated DESC', 'name')`
	 *		`ORDER BY (dated DESC, name)`
	 *
	 *	@param  string       ...$cols
	 *
	 *	@return	$this
	 */
	public function orderBy(...$cols)
	{
		$this->sql .= self::$translations['ORDER_BY'];
		$comma = null;
		foreach ($cols as $arg)
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
	 *	Generates an SQL `ORDER BY` statement
	 *
	 *	@alias order_by()
	 *
	 *	Example:
	 *
	 *		`echo sql()->ob('dated DESC', 'name')`
	 *		`ORDER BY (dated DESC, name)`
	 *
	 *	@param  string       ...$cols
	 *
	 *	@return	$this
	 */
	public function ob(...$cols)
	{
		$this->sql .= self::$translations['ORDER_BY'];
		$comma = null;
		foreach ($cols as $arg)
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
	 *	Generates an SQL `LIMIT` statement
	 *
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
	 *
	 *	@param  int       $v1
	 *	@param  int       $v2
	 *
	 *	@return	$this
	 */
	public function limit($v1, $v2 = null)
	{
		$this->sql .= self::$translations['LIMIT'] . $v1 . ($v2 === null ? null : ', ' . $v2);
		return $this;
	}

	/**
	 *	Generates an SQL `OFFSET` statement
	 *
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
	 *	@param  int       $offset
	 *
	 *	@return	$this
	 */
	public function offset($offset)
	{
		$this->sql .= self::$translations['OFFSET'] . $offset;
		return $this;
	}

	/**
	 *	`sprintf()` wrapper
	 *
	 *	Wrapper for executing an sprintf() statement, and writing
	 *		the result directly to the internal $sql string
	 *
	 *	Example:
	 *
	 *		`echo sql()->sprintf('SELECT * FROM users WHERE id = %d', $id)`
	 *		`SELECT * FROM users WHERE id = 5`
	 *
	 *	@param  string       $format The format string is composed of zero or more directives
	 *
	 *	@param  string       ...$args
	 *
	 *	@return	$this
	 */
	public function sprintf($format, ...$args)			//	http://php.net/manual/en/function.sprintf.php
	{
		$this->sql .= sprintf(...$args);		//	TODO: Detect `?` and parse the string first ???
		return $this;
	}


	/**
	 *	Prepare a given input string with given parameters
	 *
	 *	Prepares a statement for execution but write the result to the internal buffer
	 *
	 *	WARNING: This function doesn't replace the `PDO::prepare()` statement for security, only convenience!
	 *
	 *	@todo This is the central function, with constant work and room for improvements
	 *
	 *	@param string $stmt       Statement with zero or more directives
	 *
	 *	@param mixed  ...$params Values to replace and/or escape from statement
	 *
	 *	@return $this
	 */
	public function prepare($stmt, ...$params)	//	\%('.+|[0 ]|)([1-9][0-9]*|)s		somebody else's sprintf('%s') multi-byte conversion ... %s includes the ability to add padding etc.
	{
		$count = 0;
		if (count($params) === 1 && is_array($params[0])) {		//	allowing: ->prepare('SELECT name FROM user WHERE id IN (?, ?, ?)', [1, 2, 3])
			$params = $params[0];								//	problem is when the first value is for :json_encode ... we can allow ONE decode ?
			$params_conversion = true;							//	basically, this value is to support :json_encode, when there is only ONE value passed, then $params become our value, and not $params[0]!
		}
		$this->sql .= mb_ereg_replace_callback('\?\?|\\?|\\\%|%%|\\@|@@|\?|@[^a-zA-Z]?|\[.*\]|%([a-z][_a-z]*)(\:[a-z0-9\.\-:]*)*(\{[^\{\}]+\})?|%sn?(?::?\d+)?|%d|%u(?:\d+)?|%f|%h|%H|%x|%X',
							function ($matches) use (&$count, $pattern, &$params)
							{
//dump($matches);
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
//dump($params);
//dump($value);
													if (isset($params_conversion) && $params_conversion) {	//	the first $param[0] WAS an array (as test at the top) ... and there was only one value ...
														$value	=	$params;								//	$params IS an array and IS our actual value, not the first value OF params!
													}
//dump($params);
//dump($value);
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
//dump($params);
//dump($value);
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
//dump($params);
//dump($value);

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
     *	Appends text directly to the internal $sql string
     *
     *	This function implements special (non-standard) handling of arrays
     *		and is not meant to be called directly!
     *
     *	This non-standard functionality is purely for syntactic sugar
     *
     *	By implementing this function, we allow users to 'write'
     *		directly to the input string, returning $this for method chaining
     *
     *	This effectively makes EVERY array lookup/index valid
	 *		because the return value is $this
     *
     *	Strings added are NOT encoded or escaped, but simply appended
     *
     *	Special handling is provided for `null`, which will append `NULL`
     *
     *	Example:
     *
     *		`echo sql()['Hello World'];`
     *		`// Hello World`
     *
     *	Implementation of ArrayAccess::offsetGet()
     *
     *	@link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     *	@param string|null $sql This SQL text you would like to append
     *
     *	@return $this
     */
	public function offsetGet($sql)
	{
		$this->sql .= is_null($sql) ? 'NULL' : $sql;
		return $this;
	}

	public function offsetSet($idx, $sql)
	{
        throw new BadMethodCallException('Sql objects cannot be modified like this.');
	}

	public function offsetExists($index)
	{
        throw new BadMethodCallException('Sql objects cannot be modified like this.');
	}

	public function offsetUnset($index)
	{
        throw new BadMethodCallException('Sql objects cannot be modified like this.');
	}
}
