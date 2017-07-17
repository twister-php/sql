<?php

class SQL
{
	private	static $queries		=	null;
	private	static $conn		=	null;

	public	$sql				=	'';
	private	$context			=	null;	//	'SELECT', 'FROM', 'JOIN', 'WHERE'
	private	$comma				=	null;	//	reset when the `contexts` change

	/**
	 *	->SELECT('*')
	 *	OR
	 *	->SELECT() 				<<== reset $comma because nothing was supplied!
	 *		->('*')				<<== now set $comma to ', '
	 *		OR
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

	public function __construct(...$args)
	{
		// WARNING: we need to loop and parse the values!
		$this->sql = implode(null, $args);

		if (self::$conn === null) {
			$this->sql =	'** USING DUMMY CONNECTION FOR TESTING ONLY ** ' . PHP_EOL .
							'** please call SQL::setConn() with a valid MySQLi connection when you are ready! ** ' . PHP_EOL . PHP_EOL;
			self::setDummyConn();
		}
	}

	public function __toString()
	{
		return $this->sql;
	}

	/**
	 *
	 */
	public function EXPLAIN()
	{
		$this->sql = 'EXPLAIN ' . $this->sql;
		return $this;
	}

	/**
	 *	TODO: First argument should be the function
	 *	https://dev.mysql.com/doc/refman/5.7/en/call.html
	 *	CALL sp_name([parameter[,...]])
	 *	CALL sp_name[()]
	 */
	public function CALL($sp, ...$args)
	{
		$final = null;
		$comma = null;
		foreach($args as $arg)
		{
			$final .= $comma . $this->escape($arg);
			$comma = ', ';
		}
		$this->sql .= 'CALL ' . $sp . '(' . $final . ')';
		return $this;
	}


	public function SELECT(...$args)
	{
		$this->sql .= 'SELECT ' . implode(', ', $args);
		return $this;
	}
	public function SELECT_CACHE(...$args)
	{
		$this->sql .= 'SELECT SQL_CACHE ' . implode(', ', $args);
		return $this;
	}
	public function SELECT_NO_CACHE(...$args)
	{
		$this->sql .= 'SELECT SQL_NO_CACHE ' . implode(', ', $args);
		return $this;
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
	public function SELECT_DISTINCT(...$args)
	{
		$this->sql .= 'SELECT DISTINCT ' . implode(', ', $args);
		return $this;
	}
	public function DISTINCT(...$args)
	{
		$this->sql .= ' DISTINCT ' . implode(', ', $args);
		return $this;
	}
	public function SELECT_DISTINCT_CACHE(...$args)
	{
		$this->sql .= 'SELECT SQL_CACHE DISTINCT ' . implode(', ', $args);
		return $this;
	}
	public function SELECT_DISTINCT_NO_CACHE(...$args)
	{
		$this->sql .= 'SELECT SQL_NO_CACHE DISTINCT ' . implode(', ', $args);
		return $this;
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
		$this->sql .= 'LAST_INSERT_ID(' . $id . ')';
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
	public function INSERT(...$args)
	{
		$this->sql .= 'INSERT ' . empty($args) ? null : implode(' ', $args) . ' ';
		return $this;
	}

	/**
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
	 *
	 *
	 */
	public function INSERT_INTO($tbl_name, ...$args)
	{
		$this->sql .= 'INSERT ';
		return $this->INTO($tbl_name, ...$args);
	}

	/**
	 *	detect first character of column title ... if the title has '@' sign, then DO NOT ESCAPE! ... can be useful for 'DEFAULT', 'UNIX_TIMESTAMP()', or '@id' or 'MD5(...)' etc. (a connection variable) etc.
	 *
	 *	Examples:
	 *		INTO('users', 'col1', 'col2', 'col3')
	 *		INTO('users', ['col1', 'col2', 'col3'])
	 *		INTO('users', ['col1' => 'value1', 'col2' => 'value2', 'col3' => 'value3'])
	 *		INTO('users', ['col1', 'col2', 'col3'], ['value1', 'value2', 'value3'])
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
	public function INTO($tbl_name, ...$args)
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
						( ! empty($args)	?	' (' . implode(', ', $args) . ')' : null) .
						( ! empty($values)	?	' VALUES (' . implode(', ', $values) . ')' : null);
		return $this;
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
	 */
	public function VALUES(...$args)
	{
		$values = '';
		$comma = null;
		if (count($args) === 1 && is_array($args[0])) {
			$args = $args[0];
		}
		foreach ($args as $col => $arg) {
			if (is_numeric($arg)) {
				$values .= $comma . $arg;
			}
			else if ($arg === null) {
				$values .= $comma . 'NULL';
			}
			else if (is_string($arg)) {
				if (is_string($col) && $col[0] === '@') {
					$values .= $comma . $arg;
				}
				else {
					$values .= $comma . $this->returnEscaped($arg);
				}
			}
			else {
				throw new \Exception('Invalid type `' . gettype($arg) . '` sent to VALUES(); only numeric, string and null are supported!');
			}
			$comma = ', ';
		}
		$this->sql .= ' VALUES (' . $values . ')';
		return $this;
	}

	/**
	 *	Samples:
	 *	https://dev.mysql.com/doc/refman/5.7/en/insert.html
	 *	https://dev.mysql.com/doc/refman/5.7/en/update.html
	 *		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name SET col_name={expr | DEFAULT}, ... [ ON DUPLICATE KEY UPDATE col_name=expr [, col_name=expr] ... ]
	 *		UPDATE [LOW_PRIORITY] [IGNORE] table_reference SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}]
	 *
	 *		NOTE: Alternative 1: (['col1' => $value1, 'col2' => $value2]) ...
	 *		NOTE: Alternative 2: ('col1', $value1, 'col2', $value2) ...
	 *		NOTE: Alternative 3: ('col1 = ?', $value1, 'col2 = ?', $value2) ... too much work ... NOT SUPPORTED YET!
	 */
	public function SET(...$args)
	{
		$values = '';
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
	public function FROM(...$args)
	{
		$this->sql .= PHP_EOL . 'FROM ' . implode(', ', $args);
		return $this;
	}

	public function JOIN(...$args)
	{
		$this->sql .= PHP_EOL . "\tJOIN " . implode(', ', $args);
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
	public function LEFT_JOIN(...$args)
	{
		$this->sql .= PHP_EOL . "\tLEFT JOIN " . implode(', ', $args);
		return $this;
	}

	/**
	 *	Example:
	 *		.USING('id')
	 *		.USING('id', 'user_id')	??? legal??
	 *
	 *	Sample:
	 *		t1 LEFT JOIN t2 USING (id) LEFT JOIN t3 USING (id)
	 */
	public function USING(...$args)
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
	public function ON(...$args)
	{
		$this->sql .= ' ON (' . implode(' AND ', $args) . ')';
		return $this;
	}

	/**
	 *	Examples:
	 *		LEFT JOIN (t2, t3, t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 *		LEFT JOIN (t2 CROSS JOIN t3 CROSS JOIN t4) ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)
	 */
	public function AND(...$args)
	{
	//	$this->sql .= ' AND (' . $and . ')';
		$this->sql .= ' AND ' . implode(' AND ', $args);
		return $this;
	}

	public function WHERE(...$args)
	{
		$this->sql .= PHP_EOL . 'WHERE ' . implode(' AND ', $args);
		return $this;
	}

	/**
	 *	Example:
	 *		.IS_NULL() ==> $sql .= ' IS NULL'
	 *		.IS_NULL($field) ==> $sql .= $field . ' IS NULL'
	 *
	 *	Sample:
	 *		WHERE key_col IS NULL
	 */
	public function IS_NULL($field = null)
	{	//	TODO, we could detect if a value was input ... if is_null($input) ... then do something else !?!?
		$this->sql .= $field . ' IS NULL';
		return $this;
	}


	/**
	 *	Escapes the input value, and replaces the '?'
	 *	
	 *	Example:
	 *		.LIKE('?%', $id)
	 *
	 *
	 *	Samples:
	 *		WHERE key_col LIKE 'ab%'
	 */
	public function LIKE($sequence, $value)
	{
		//$sql .= ' LIKE "' . str_replace(...) . '"';
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
	public function COUNT($col = '*', $as = null)
	{
		$this->sql .= ' COUNT(' . $col . ($as !== null ? ') AS ' . $as : ')');
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
	public function COUNT_AS($col, $as = null)
	{
		if ($as === null) {
			//	TODO: check $col for invalid characters if $as === null! because we need to append a col name and not some agregate function!
			
		}
		$this->sql .= ' COUNT(' . $col . ') AS ' . $as ?: ('count_of_' . $col);		///	WARNING: ... need to only get the first part of `work.artist_id`
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
	public function AS($as = null)
	{
		$this->sql .= ' AS ' . $as;
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
	public function MIN($col, $as = null)
	{
		$this->sql .= ' MIN(' . $col . ($as !== null ? ') AS ' . $as : ')');
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
	public function MIN_AS($col, $as = null)
	{
		if ($as === null) {
			//	TODO: check $col for invalid characters if $as === null! because we need to append a col name and not some agregate function!
		}
		$this->sql .= ' MIN(' . $col . ') AS ' . $as ?: ('min_' . $col);
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
	public function MIN_DISTINCT($col, $as = null)
	{
		$this->sql .= ' MIN(DISTINCT ' . $col . ($as !== null ? ') AS ' . $as : ')');
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
	public function MIN_DISTINCT_AS($col, $as = null)
	{
		$this->sql .= ' MIN(DISTINCT ' . $col . ') AS ' . $as ?: ('min_' . $col);
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
	public function MAX($max)
	{
		throw new \Exception('Need to copy the MIN() handlers to MAX() when I am done!');
		$this->sql .= ' MAX(' . $max . ')';
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
	public function SUM($col)
	{
		$this->sql .= ' SUM(' . $col . ')';
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
	public function IN(...$args)
	{
		$this->sql .= ' IN (' . implode(null, $args) . ')';
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
	 *		.BETWEEN('age', $min, $max)
	 *		.('age').BETWEEN($min, $max)
	 *		.WHERE('age').BETWEEN($min, $max)
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
	 *		.SUM(5)
	 *
	 *	Samples:
	 *		max($min, min($max, $current));
	 */
	public function CLAMP($value, $min, $max)
	{
		throw new \Exception('CLAMP not implemented yet');
		$this->sql .= ' (IF ' . $max . ', )';
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
	public function UNION(...$args)
	{
		$this->sql .= ' UNION ' . implode(null, $args);
		return $this;
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
	public function ORDER_BY(...$args)
	{
		$this->sql .= PHP_EOL . 'ORDER BY ' . implode(', ', $args);
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
	 *		.LIMIT(5, 10)
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
	public function LIMIT($v1, $v2 = null)
	{
		$this->sql .= PHP_EOL . 'LIMIT ' . $v1 . ($v2 === null ? null : '');
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
	 *		.LIMIT(5, 10)
	 *
	 *	Samples:
	 *		
	 */
	public function OFFSET($offset)
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
		static $lower_under	=	['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',	//	array used to 'replace' the $name, if the replacement == empty string then ALL the characters are in this range
								'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '_'];

		static $translations	=	[	'SELECT'		=>	'SELECT ',				//	https://dev.mysql.com/doc/refman/5.7/en/select.html
										'DELETE'		=>	'DELETE ',				//	https://dev.mysql.com/doc/refman/5.7/en/delete.html
										'INSERT'		=>	'INSERT ',
										'CALL'			=>	'CALL ',

										'CREATE'		=>	'CREATE ',
										'DROP'			=>	'DROP ',
										'CREATE_TABLE'	=>	'CREATE TABLE ',
										'ALTER'			=>	'ALTER ',
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
										'SMALL'			=>	'SQL_SMALL_RESULT ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_BIG_RESULT or SQL_SMALL_RESULT can be used with GROUP BY or DISTINCT to tell the optimizer that the result set has many rows or is small, respectively.
										'BIG'			=>	'SQL_BIG_RESULT ',		//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_BIG_RESULT or SQL_SMALL_RESULT can be used with GROUP BY or DISTINCT to tell the optimizer that the result set has many rows or is small, respectively.
										'BUFFER'		=>	'SQL_BUFFER_RESULT ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_BUFFER_RESULT forces the result to be put into a temporary table. This helps MySQL free the table locks early and helps in cases where it takes a long time to send the result set to the client. This modifier can be used only for top-level SELECT statements, not for subqueries or following UNION.
										'CACHE'			=>	'SQL_CACHE ',			//	https://dev.mysql.com/doc/refman/5.7/en/select.html		The SQL_CACHE and SQL_NO_CACHE modifiers affect caching of query results in the query cache (see Section 8.10.3, “The MySQL Query Cache”). SQL_CACHE tells MySQL to store the result in the query cache if it is cacheable and the value of the query_cache_type system variable is 2 or DEMAND. With SQL_NO_CACHE, the server does not use the query cache. It neither checks the query cache to see whether the result is already cached, nor does it cache the query result.
										'NO_CACHE'		=>	'SQL_NO_CACHE ',		//	https://dev.mysql.com/doc/refman/5.7/en/select.html		The SQL_CACHE and SQL_NO_CACHE modifiers affect caching of query results in the query cache (see Section 8.10.3, “The MySQL Query Cache”). SQL_CACHE tells MySQL to store the result in the query cache if it is cacheable and the value of the query_cache_type system variable is 2 or DEMAND. With SQL_NO_CACHE, the server does not use the query cache. It neither checks the query cache to see whether the result is already cached, nor does it cache the query result.
										'CALC'			=>	'SQL_CALC_FOUND_ROWS ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html		SQL_CALC_FOUND_ROWS tells MySQL to calculate how many rows there would be in the result set, disregarding any LIMIT clause. The number of rows can then be retrieved with SELECT FOUND_ROWS(). See Section 12.14, “Information Functions”.
										'SQL_CALC_FOUND_ROWS'=>	'SQL_CALC_FOUND_ROWS ',	//	https://dev.mysql.com/doc/refman/5.7/en/select.html	SQL_CALC_FOUND_ROWS tells MySQL to calculate how many rows there would be in the result set, disregarding any LIMIT clause. The number of rows can then be retrieved with SELECT FOUND_ROWS(). See Section 12.14, “Information Functions”.

										'DELAYED'		=>	'DELAYED ',				//	https://dev.mysql.com/doc/refman/5.7/en/insert.html		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name

										'LOW_PRIORITY'	=>	'LOW_PRIORITY ',		//	https://dev.mysql.com/doc/refman/5.7/en/delete.html		DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
										'LOW'			=>	'LOW_PRIORITY ',		//	https://dev.mysql.com/doc/refman/5.7/en/delete.html		DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
										'QUICK'			=>	'QUICK ',				//	https://dev.mysql.com/doc/refman/5.7/en/delete.html		DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
										'IGNORE'		=>	'IGNORE ',				//	https://dev.mysql.com/doc/refman/5.7/en/delete.html		DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name

										'TRUNCATE'		=>	'TRUNCATE ',			//	https://dev.mysql.com/doc/refman/5.7/en/truncate-table.html		TRUNCATE [TABLE] tbl_name
										'TRUNCATE_TABLE'=>	'TRUNCATE TABLE ',		//	https://dev.mysql.com/doc/refman/5.7/en/truncate-table.html		TRUNCATE [TABLE] tbl_name

										'COUNT_ALL'		=>	'COUNT(*)',
										'COUNT'			=>	'COUNT',
										'LAST_INSERT_ID'=>	'LAST_INSERT_ID()',		//	SELECT LAST_INSERT_ID();	UPDATE sequence SET id=LAST_INSERT_ID(id+1);
										'ROW_COUNT'		=>	'ROW_COUNT()',			//	https://dev.mysql.com/doc/refman/5.7/en/information-functions.html#function_row-count		SELECT ROW_COUNT();
										'A'				=>	'*',					//	`ALL` is a SELECT modifier ... gonna change its meaning!
										'STAR'			=>	'*',
										'CA'			=>	'COUNT(*)',
										'C'				=>	'COUNT',				//	COUNT or COMMA or CLOSE  ???
									//	'C'				=>	', ',
									//	'C'				=>	')',

										'FROM'			=>	PHP_EOL . 'FROM ',
										'JOIN'			=>	PHP_EOL . 'JOIN ',
										'LEFT_JOIN'		=>	PHP_EOL . 'LEFT JOIN ',
										'WHERE'			=>	PHP_EOL . 'WHERE ',
										'GROUP_BY'		=>	PHP_EOL . 'GROUP BY ',
										'HAVING'		=>	PHP_EOL . 'HAVING ',
										'ORDER_BY'		=>	PHP_EOL . 'ORDER BY ',
										'LIMIT'			=>	PHP_EOL . 'LIMIT ',
										'PROCEDURE'		=>	PHP_EOL . 'PROCEDURE ',
										'INTO_OUTFILE'	=>	PHP_EOL . 'INTO OUTFILE ',
										'UNION'			=>	PHP_EOL . 'UNION' . PHP_EOL,

										'S'				=>	'SELECT ',
										'D'				=>	'DELETE ',
										'I'				=>	'INSERT ',
										'F'				=>	PHP_EOL . 'FROM ',
										'J'				=>	PHP_EOL . 'JOIN ',
										'LJ'			=>	PHP_EOL . 'LEFT JOIN ',
										'W'				=>	PHP_EOL . 'WHERE ',
										'O'				=>	PHP_EOL . 'ORDER BY ',
										'OB'			=>	PHP_EOL . 'ORDER BY ',
										'L'				=>	PHP_EOL . 'LIMIT ',
										'U'				=>	PHP_EOL . 'UNION' . PHP_EOL,

										'DESC'			=>	' DESC',
										'ASC'			=>	' ASC',
										'IN'			=>	' IN ',
										'NOT_IN'		=>	' NOT IN ',
										'NOT'			=>	' NOT',
										'NULL'			=>	' NULL',
										'CHARACTER_SET'	=>	' CHARACTER SET ',					//	[INTO OUTFILE 'file_name' [CHARACTER SET charset_name]
										'INTO_DUMPFILE'	=>	' INTO DUMPFILE ',					//	[INTO OUTFILE 'file_name' [CHARACTER SET charset_name] export_options | INTO DUMPFILE 'file_name'
										'DUMPFILE'		=>	'DUMPFILE ',						//	[INTO OUTFILE 'file_name' [CHARACTER SET charset_name] export_options | INTO DUMPFILE 'file_name'
																								//	INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name
										'INTO'			=>	'INTO ',							//	[INTO OUTFILE 'file_name' [CHARACTER SET charset_name] export_options | INTO DUMPFILE 'file_name' | INTO var_name [, var_name]]
										'OFFSET'		=>	' OFFSET ',							//	[LIMIT {[offset,] row_count | row_count OFFSET offset}]

										//	These can only come at the end of a SELECT, not sure if they can be used in other statements?
										'FOR_UPDATE'					=>	PHP_EOL . 'FOR UPDATE',							//	[FOR UPDATE | LOCK IN SHARE MODE]]
										'LOCK_IN_SHARE_MODE'			=>	' LOCK IN SHARE MODE',							//	[FOR UPDATE | LOCK IN SHARE MODE]]
										'FOR_UPDATE_LOCK_IN_SHARE_MODE'	=>	PHP_EOL . 'FOR UPDATE LOCK IN SHARE MODE',		//	[FOR UPDATE | LOCK IN SHARE MODE]]

										'ON_DUPLICATE_KEY_UPDATE'		=>	PHP_EOL . 'ON DUPLICATE KEY UPDATE ',				//	https://dev.mysql.com/doc/refman/5.7/en/insert.html

										'AUTO_INCREMENT'=>	' AUTO_INCREMENT',					//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'INT'			=>	' INT',								//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'PK'			=>	'PRIMARY KEY ',						//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'PRIMARY_KEY'	=>	'PRIMARY KEY ',						//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'UNIQUE_KEY'	=>	'UNIQUE KEY ',						//	CREATE TABLE `t` `id` INT(11) NOT NULL AUTO_INCREMENT, `val` INT(11) DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `i1` (`val`)
									//	'PRIMARY'		=>	'PRIMARY ',							//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))	//	needs work ...
									//	'KEY'			=>	'KEY ',								//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b))
										'ENGINE'		=>	PHP_EOL . 'ENGINE',					//	CREATE TABLE test (a INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (a), KEY(b)) ENGINE=MyISAM SELECT b,c FROM test2;

										'IF'			=>	' IF ',
										'SET'			=>	' SET ',
										'COMMA'			=>	', ',
										'c_'			=>	', ',	//	currently the only lower case, case ... how else do we get a comma???
										'_'				=>	', ',	//	space or comma?
										'__'			=>	', ',	//	space or comma?
										'Q'				=>	'"',
										'SPACE'			=>	' ',
										'_O'			=>	'(',	//	OP	? || O
										'C_'			=>	')',	//	CL	? || C
										'OPEN'			=>	'(',
										'CLOSE'			=>	')',
										'TAB'			=>	"\t",
										'NL'			=>	"\n",
										'CR'			=>	"\r",
										'EOL'			=>	PHP_EOL,
										'BR'			=>	PHP_EOL,
										'EQ'			=>	' = ',
										'NEQ'			=>	' != ',
										'NOTEQ'			=>	' != ',
										'NOT_EQ'		=>	' != ',
										'GT'			=>	' > ',
										'LT'			=>	' < ',
										'AS'			=>	' AS ',
										'ON'			=>	' ON ',
										'AND'			=>	' AND ',
										'OR'			=>	' OR ',
										'BETWEEN'		=>	' BETWEEN ',

										'OUT'			=>	'OUT ',								//	https://dev.mysql.com/doc/refman/5.7/en/call.html		CREATE PROCEDURE p (OUT ver_param VARCHAR(25), INOUT incr_param INT)
										'INOUT'			=>	'INOUT ',							//	https://dev.mysql.com/doc/refman/5.7/en/call.html		CREATE PROCEDURE p (OUT ver_param VARCHAR(25), INOUT incr_param INT)
										'INOUT'			=>	'INOUT ',							//	https://dev.mysql.com/doc/refman/5.7/en/call.html		CREATE PROCEDURE p (OUT ver_param VARCHAR(25), INOUT incr_param INT)

																								//	INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)]
										'PARTITION'		=>	PHP_EOL . 'PARTITION ',				//	https://dev.mysql.com/doc/refman/5.7/en/select.html		[FROM table_references [PARTITION partition_list]
										'WITH_ROLLUP'	=>	' WITH ROLLUP ',					//	https://dev.mysql.com/doc/refman/5.7/en/select.html		[GROUP BY {col_name | expr | position} [ASC | DESC], ... [WITH ROLLUP]]
										'DEFAULT'		=>	' DEFAULT ',

										''			=>	'',
										''			=>	'',
										''			=>	'',
										''			=>	'',
										''			=>	'',
										''			=>	'',
										''			=>	'',
										''			=>	'',
										''			=>	'',
										''			=>	'',
										''			=>	'',
										''			=>	'',
									];

		switch ($name)
		{
			//	common
			case 'SELECT':			$this->sql .= 'SELECT ';					$this->comma = null;	$this->context = 'SELECT';	break;
			case 'COUNT_ALL':		$this->sql .= $this->comma . 'COUNT(*)'; 	$this->comma = ', ';	break;
			case 'ALL':				$this->sql .= $this->comma . '*';		 	$this->comma = ', ';	break;		// `ALL` is a SELECT keyword NOT the same as COUNT(*) ... gonna change it's meaning, because `STAR` looks weird!
			case 'STAR':			$this->sql .= $this->comma . '*';		 	$this->comma = ', ';	break;
			case 'FROM':			$this->sql .= PHP_EOL . 'FROM ';			$this->comma = null;	$this->context = 'FROM';	break;
			case 'JOIN':			$this->sql .= PHP_EOL . 'JOIN ';			$this->comma = null;	$this->context = 'JOIN';	break;
			case 'LEFT_JOIN':		$this->sql .= PHP_EOL . 'LEFT JOIN ';		$this->comma = null;	$this->context = 'JOIN';	break;
			case 'WHERE':			$this->sql .= PHP_EOL . 'WHERE ';			$this->comma = null;	$this->context = 'WHERE';	break;
			case 'GROUP_BY':		$this->sql .= PHP_EOL . 'GROUP BY ';		$this->comma = null;	$this->context = 'GROUP';	break;
			case 'HAVING':			$this->sql .= PHP_EOL . 'HAVING ';			$this->comma = null;	$this->context = 'HAVING';	break;
			case 'ORDER_BY':		$this->sql .= PHP_EOL . 'ORDER BY ';		$this->comma = null;	$this->context = 'ORDER';	break;
			case 'LIMIT':			$this->sql .= PHP_EOL . 'LIMIT ';			$this->comma = null;	$this->context = 'LIMIT';	break;
			case 'PROCEDURE':		$this->sql .= PHP_EOL . 'PROCEDURE ';		$this->comma = null;	$this->context = null;		break;
			case 'INTO_OUTFILE':	$this->sql .= PHP_EOL . 'INTO OUTFILE ';	$this->comma = null;	$this->context = null;		break;
			case 'UNION':			$this->sql .= PHP_EOL . 'UNION' . PHP_EOL;	$this->comma = null;	$this->context = null;		break;

			case 'CALL':			$this->sql .= 'CALL ';	$this->comma = null;	$this->context = 'CALL';	break;	//	https://dev.mysql.com/doc/refman/5.7/en/call.html

			//	uncommon
		//	case 'DESC':		$this->sql .= ' DESC'; break;
		//	case 'DISTINCT':	$this->sql .= 'DISTINCT '; break;
		//	case 'ASC':			$this->sql .= ' ASC'; break;

		//	case 'IN':			$this->sql .= ' IN '; break;						//	default case
		//	case 'NOT_IN':		$this->sql .= ' NOT IN '; break;					//	default case
		//	case 'NOT':			$this->sql .= ' NOT '; break;						//	default case
		//	case 'EQ':			$this->sql .= ' = '; break;							//	default case
		//	case 'GT':			$this->sql .= ' > '; break;							//	default case
		//	case 'LT':			$this->sql .= ' < '; break;							//	default case
		//	case 'AS':			$this->sql .= ' AS '; break;						//	default case
		//	case 'ON':			$this->sql .= ' ON '; break;						//	default case
		//	case 'AND':			$this->sql .= ' AND '; break;						//	default case
		//	case 'BETWEEN':		$this->sql .= ' BETWEEN '; break;					//	default case

			default:

				if (isset($translations[$name])) {
					$this->sql .= $translations[$name];
				}
				else if (str_replace($lower_under, null, $name) === '') {
					//	string contains ALL lowercase values and underscores ... ie. probably a table/field/column name! Leave unchanged!
					$this->sql .= $name;
				}
				else {
					$this->sql .= ' ' . $name . ' '; // `unknown`
				}
		}
		return $this;
	}

	/**
	 *	$sql = SQL()->();
	 *	$sql = SQL()->SELECT->STAR->FROM->users->();	//	'SELECT * FROM users'
	 *	$sql = SQL()->ORDER_BY->price->DESC->();		//	'ORDER BY price DESC'
	 */
	public function __invoke(...$args)
	{
die('__invoke');
		return $this->sql;
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



	public static function setBuilderLocation($queries)
	{
		self::$queries = $queries;
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
	public function escape(string $value)
	{
		$this->sql .= '"' . self::$conn->real_escape_string(self::utf8($value)) . '"';
		return $this;
	}
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
	 *	Used when we detect ? ...
	 *
	 */
	public function sanitize($value)
	{
		if (is_numeric($value)) return $value;
		if (is_string($value)) return '"' . self::$conn->real_escape_string(self::utf8($value)) . '"';
		if (is_null($value)) return 'NULL';
		foreach ($value as $key => &$v)
			$v = $this->sanitize($v);
		return '(' . implode(', ', $value) . ')';
	}

	/**
	 *	Add (optional) quote marks on strings ... maybe we make `escape` a wrapper around `real_escape_string` which does NOT add quotes!
	 *	
	 */
	public function quote(string $value, $escape = true)
	{
		$this->sql .= '"' . ($escape ? self::$conn->real_escape_string(self::utf8($value)) : $value) . '"';
		return $this;
	}

}

/**
 *	Helper function to build a new SQL query object, just saves using `new` :p
 */
function SQL(...$args)
{
	return new SQL(...$args);
}
