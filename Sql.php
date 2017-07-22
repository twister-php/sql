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

// Load the global sql() function
require_once __DIR__ . '/functions/sql.php';

if (version_compare(phpversion(), '5.6.0', '>=')) {
$instance = new $class(eval('...') . $args);
} else {
	require_once __DIR__ . '/functions/sql-old.php';
}


/**
 *	SQL Query Builder
 *
 *	@package     SQL Query Builder
 *	@description Rapidly construct raw SQL query strings
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
											'OR'			=>	'OR',
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
	 *		and an optional list of $params
	 *
	 *	The object can be initialized in multiple ways:
	 *		but operates very much like `sprintf()` and `PDO::prepare()`
	 *
	 *	Creates new statement with the powerful `prepare()` syntax
	 *
	 *	Examples:
	 *
	 *		$sql = new Sql();
	 *		$sql = new Sql('?', $value);
	 *		$sql = new Sql('%s', $value);
	 *		$sql = new Sql('SELECT * FROM users');
	 *		$sql = new Sql('SELECT * FROM users WHERE id = ?', $id);
	 *
	 *	@param  string|null $stmt (optional) Statement in `prepare()` syntax, all `?`, `@` and `%` values must be escaped!
	 *	@param  mixed[]     $params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return void
	 */
	public function __construct(string $stmt = null, ...$params)
	{
		if (empty($params)) {
			$this->sql = $stmt;
		}
		else {
			$this->prepare($stmt, ...$params);
		}
	}

}

/**
 *	Helper function to build a new {@see \Sql} query object
 * Builds a {@see \Closure} capable {@see \Sql} of instantiating the given $className without
 *
 *
 *	Helper function to build a new {@see \Sql} query object
 *
 *	@param  string|null $stmt (optional)
 *
 *	@param  mixed       $params
 *                      (optional) Parameters associated with $stmt
 *
 *
 *	@return Sql Returns a new Sql instance
 *
 */
function sql(string $stmt = null, ...$params) : Sql
{
	return new Sql($stmt, ...$params);
}
