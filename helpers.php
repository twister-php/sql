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
/**
 *	SQL Query Builder - global helper functions
 *
 *	@package     SQL Query Builder
 *	@author      Trevor Herselman <therselman@gmail.com>
 *	@copyright   Copyright (c) 2017 Trevor Herselman
 *	@license     http://opensource.org/licenses/MIT
 *	@link        https://github.com/twister-php/sql
 */

if ( ! function_exists('sql')) {
	/**
	 *	Helper function to build a new Twister\Sql query objects
	 *
	 *	Construct a new Twister\Sql SQL query builder or statement,
	 *		normally initialized by the optional $stmt string
	 *		and an optional list of $params
	 *
	 *	Can be full or partial queries, fragments or statements
	 *
	 *	The object can be initialized in multiple ways:
	 *		but operates similar to `sprintf()` and `PDO::prepare()`
	 *
	 *	Examples:
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
	 *		`$name = "Trevor's";`
	 *		`echo sql('SELECT * FROM users WHERE name = ?', $name);`
	 *		`SELECT * FROM users WHERE name = "Trevor\'s"`			//	UTF-8 aware escapes
	 *
	 *
	 *	@param  string|null $stmt
	 *                      (optional) Statement in `prepare()` or `sprintf()` syntax;
	 *                      Therefore all raw `?`, `@` and `%` values must be escaped!
	 *
	 *	@param  mixed       $params
	 *                      (optional) Parameters associated with $stmt
	 *
	 *	@return Sql Returns a new Twister\Sql instance
	 *
	 */
	function sql($stmt = null, ...$params)
	{
		return new Twister\Sql($stmt, ...$params);
	}
}



if ( ! function_exists('mb_trim')) {
	/**
	 *	Multibyte-aware version of `trim()`
	 *
	 *	This function uses the Multibyte extention, and will therefore rely on
	 *		the internal character encoding (@see `mb_internal_encoding()`)
	 *
	 *	Since Multibyte strings have a much larger range of code-points,
	 *		such as 18 different spaces,
	 *		I believe it's more prudent to expand the range of trimmed characters;
	 *		therefore this function trims the expanded ranges by default
	 *
	 *	Examples:
	 *
	 *		`mb_trim($str)`				//	default, enhanced whitespace removal
	 *		`mb_trim($str, '\s')`		//	custom mask
	 *		`mb_trim($str, true)`		//	use 100% trim() compatible mask
	 *
	 *	Removes the following leading and trailing characters:
	 *
	 *	- \p{Z} - 'any kind of whitespace or invisible separator.' (18 different spaces)
	 *
	 *	- \p{C} - 'invisible control characters and unused code points'
	 *
	 *	- Removes leading and trailing whitespace, control characters, formatting,
	 *		surrogate characters and any invalid/unused/private character codes
	 *
	 *	@link http://php.net/manual/en/function.trim.php
	 *
	 *	@author Trevor Herselman <therselman@gmail.com>
	 *
	 *	@param  string      $str  String to trim
	 *	@param  string|true $mask Custom character mask or true to use a trim() compatible mask
	 *	@return string
	 */
	function mb_trim($str, $mask = '\p{Z}\p{C}')
	{
		if ($mask === true) {
			$mask = ' \x09\x0A\x0D\x00\x0B'; //	trim() mask = " \t\n\r\0\v"
		}
		return mb_ereg_replace('^[' . $mask . ']+|[' . $mask . ']+$', null, $str);
	}
}

if ( ! function_exists('mb_rtrim')) {
	/**
	 *	Multibyte-aware version of `rtrim()`
	 *
	 *	Strip whitespace (or other characters) from the end of a string
	 *
	 *	@see mb_trim()
	 *
	 *	@link http://php.net/manual/en/function.rtrim.php
	 *
     *	@author Trevor Herselman <therselman@gmail.com>
	 *
	 *	@param  string      $str  String to trim
	 *	@param  string|true $mask Custom character mask or true to use a trim() compatible mask
	 *	@return string
	 */
	function mb_rtrim($str, $mask = '\p{Z}\p{C}')
	{
		if ($mask === true) {
			$mask = ' \x09\x0A\x0D\x00\x0B'; //	trim() mask = " \t\n\r\0\v"
		}
		return mb_ereg_replace('[' . $mask . ']+$', null, $str);
	}
}

if ( ! function_exists('mb_ltrim')) {
	/**
	 *	Multibyte-aware version of `ltrim()`
	 *
	 *	Strip whitespace (or other characters) from the beginning of a string
	 *
	 *	@see mb_trim()
	 *
	 *	@link http://php.net/manual/en/function.ltrim.php
	 *
     *	@author Trevor Herselman <therselman@gmail.com>
	 *
	 *	@param  string      $str  String to trim
	 *	@param  string|true $mask Custom character mask or true to use a trim() compatible mask
	 *	@return string
	 */
	function mb_ltrim($str, $mask = '\p{Z}\p{C}')
	{
		if ($mask === true) {
			$mask = ' \x09\x0A\x0D\x00\x0B'; //	trim() mask = " \t\n\r\0\v"
		}
		return mb_ereg_replace('^[' . $mask . ']+', null, $str);
	}
}

if ( ! function_exists('utf8_sanitize')) {
	/**
	 *	UTF-8 string sanitizer
	 *
	 *	Anything that would cause `preg_replace()` (with the `/u` flag) or `mb_ereg_replace()`
	 *	to return a `null` value is regarded as `irregular` / non-standard UTF-8 input, and should be 'sanitized'.
	 *
	 *	The current method of using `mb_convert_encoding()` internally
	 *	effectively 'neutralizes' invalid sequences from causing harm.
	 *
	 *	Demonstrating that both `preg_replace()` and `mb_ereg_replace()` return a `null` value
	 *	on invalid sequences can easily be demonstrated with the following:
	 *
	 *	Using the 'symfony/var-dumper' function:
	 *
	 *		dump(preg_replace('/\t/u', 'X', "\t"));
	 *		dump(preg_replace('/\t/u', 'X', "\t\xe2\x28\xa1"));		//	"\xe2\x28\xa1" is invalid UTF-8
	 *
	 *		dump(mb_ereg_replace('\t', 'X', "\t"));
	 *		dump(mb_ereg_replace('\t', 'X', "\t\xe2\x28\xa1"));
	 *
	 *	Output:
	 *
	 *		"X"
	 *		null
	 *
	 *		"X"
	 *		null
	 *
	 *	@link https://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
	 *
     *	@author Trevor Herselman <therselman@gmail.com>
	 *
	 *	@param  string      $str  String to trim
	 *	@param  string|true $mask Custom character mask or true to use a trim() compatible mask
	 *	@return string
	 */
	function utf8_sanitize($value, $from_enc = 'UTF-8')
	{
		return mb_convert_encoding($value , 'UTF-8', $from_enc);
	}


	/**
	 *	UTF-8 array sanitizer
	 *
	 *	'sanitizes' an array, including multi-dimentional arrays!
	 *
	 *	@see utf8_sanitize()
	 *
	 *	Because user input, especially from forms can cause issues,
	 *	as it's not common practice to check `preg_replace()` or `mb_ereg_replace()`
	 *	for a `null` value on return,
	 *	which can cause unforeseen issues or even security vulnerabilities;
	 *	I would recommend the following before dealing with user input:
	 *
	 *	`$_GET  = utf8_sanitize_array($_GET);`
	 *	`$_POST = utf8_sanitize_array($_POST);`
	 *
	 *	`$_COOKIE = utf8_sanitize_array($_COOKIE);` // (optional)
	 *
	 *	Immagine a bot/spider posting invalid binary sequences in your forms,
	 *	causing an SQL query to fail or generate invalid sequences or expose critical data;
	 *	because a `preg_*(/../u)` sequence failed and sent a `NULL` value to the database;
	 *	you wouldn't even know about it because it's hard to test for invalid UTF-8 sequences.
	 *	And you wouldn't normally test if preg_replace() result == null then ...
	 *
     *	@author Trevor Herselman <therselman@gmail.com>
	 *
	 *	@param  string      $arr  String to sanitize
	 *	@param  string|true $mask Custom character mask or true to use a trim() compatible mask
	 *	@return string
	 */
	function utf8_sanitize_array($arr, $from_enc = 'UTF-8')
	{
		foreach($arr as $key => $value) {
			if (is_string($value)) {
				$arr[$key] = mb_convert_encoding($value , 'UTF-8', $from_enc);
			}
			else if (is_array($value)) {
				$arr[$key] = utf8_sanitize_array($value, $from_enc);
			}
		}
		return $arr;
	}
}

if ( ! function_exists('mb_normalize')) {
	/**
	 *	Multibyte-aware string normalizer
	 *
	 *
     *	@author Trevor Herselman <therselman@gmail.com>
	 *
	 *	@param  string      $str  String to trim
	 *	@param  string|true $mask Custom character mask or true to use a trim() compatible mask
	 *	@return string
	 */
	function mb_normalize($str, $mask = '(?!\s)\p{C}')
	{
		return mb_ereg_replace($mask, null, $str);
	}
}

if ( ! function_exists('mb_pack')) {
    /**
     *	Multibyte string packer (trim + sanitize + whitespace merging)
     *
     *	Merges/condenses all whitespace characters (Unicode defines 18 different spaces)
     *	Removes control, formatting, invalid and surrogate characters
     *
     *	This function is similar to replacing `\s+` with a space.
     *	Except more powerful, because it detects hundreds of characters not covered by `\s`
     *
     *	- Removes \p{C} ('invisible control characters and unused code points')
     *		'control characters' include \0\t\r\n
	 *		BUT we exclude \s characters (\t\r\n); note \0 (NUL) is not part of \s
     *	- Removes leading and trailing whitespace and control characters (trim)
     *	- Remove text formatting and surrogate characters
     *	- Remove 'any code point reserved for private use'
     *	- Remove 'any code point to which no character has been assigned'
     *	- Merge all line, paragraph and word separators ( \t\r\n)
     *	- Merge all \p{Z} characters ('any kind of whitespace or invisible separator.')
     *		as well as the remaining \s characters, which include \t\r\n
     *
	 *	@author Trevor Herselman <therselman@gmail.com>
     *
	 *	@param  string $str
	 *	@return string Packed $str
	 */
	function mb_pack($str)
	{
		return	mb_ereg_replace('(?! )[\p{Z}\s]+', ' ',
				mb_ereg_replace('^[\p{Z}\s]+|(?!\s)\p{C}|[\p{Z}\s]+$', null, $str));
	}
/*
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
*/
}


/**
 *	Supplementary reference information for mb_trim() and mb_pack()
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
 *	`trim()`:  `" ,\t,\r,\n,\v,\0"`   `\v` == \x0B == vertical tab (decimal:11)   `\n` = line feed   `\t` = tab   `\r` = carriage return
 *
 *	`\s`:      `" ,\t,\r,\n,\v,\f"`   `\f` == \x0C == form feed (decimal:12)
 *
 *	ctrl-Z = decimal:26	symbol: ^Z  / SUB  / EOF
 *
 *	'Control character' class:		https://en.wikipedia.org/wiki/Control_character
 *
 *	`\s` includes characters in two different classes
 *		`\t`, `\r`, `\n` are defined in the \p{Cc} class
 *		` ` (space) is defined in the \p{Zs} class
 *
 *	Supplementary Information from:
 *		http://nadeausoftware.com/articles/2007/9/php_tip_how_strip_punctuation_characters_web_page#Removinglineparagraphandwordseparators
 *
 *  \p{Z}  -   'Removing line, paragraph, and word separators'
 *             'Separator characters delimit lines, paragraphs, and words.
 *              The most common separator is a space character, but Unicode defines 18 different spaces,
 *              such as n- and m-sized spaces, and a non-breaking space.
 *              Replace all of these with a generic space to simplify content analysis and further regular expressions.'
 *
 *		Remove control, formatting, and surrogate characters
 *		`$text = preg_replace( '/[\p{Cc}\p{Cf}\p{Cs}]/u', ' ', $text );`
 */
