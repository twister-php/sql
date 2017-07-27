# Raw SQL Query Builder
Raw SQL Query Builder ~ the Swiss-army knife of raw SQL queries

## Introduction

We already have some great tools when working with managed or abstracted database layers like ORM's and Doctrine DBAL. And most ORM's allow you to write and execute raw SQL queries when you require greater/custom flexibility or functionality they don't provide.

However, what tools do you have when working with the plain text strings of raw/native SQL queries? You have lots of string concatenations, [`implode()`](http://php.net/manual/en/function.implode.php), [`PDO::prepare`](http://php.net/manual/en/pdo.prepare.php), [`PDO::quote`](http://php.net/manual/en/pdo.quote.php), [`sprintf`](http://php.net/manual/en/function.sprintf.php) (for the brave) and [`mysqli::real_escape_string`](http://php.net/manual/en/mysqli.real-escape-string.php) because the first version wasn't real enough or the name long enough.

## The One Ring to rule them all, One Ring to bind them

Introducing the '[Raw SQL Query Builder](https://github.com/twister-php/sql)' (SQLQB); combining all the functionality of having placeholders like `?`, `:id`, `%s`, `%d`; with an ORM style '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)' (methods `return $this` for method-chaining) and much more.

It's the glue that sits between `$sql = '...';` and `$db->query($sql)`. The part where you have to concatenate, 'escape', 'quote', 'prepare' and 'bind' values in a raw SQL query string.

This is not an ORM or replacement for an ORM, it's the tool you use when you need to create a raw SQL query string with the convenience of placeholders. It doesn't 'prepare' or 'execute' your queries exactly like `PDO::prepare` does; but it does support the familiar syntax of using `?` or `:id` as placeholders. It also supports a subset of `sprintf`'s `%s` / `%d` syntax.

In addition, it supports inserting 'raw' strings (without quotes or escapes) with `@`; eg. `sql('dated = @', 'NOW()')`, even replacing column or table names as well as auto-`implode()`ing arrays with `[]` eg. `sql('WHERE id IN ([])', $array')`


```php
echo sql('SELECT * FROM @ WHERE @ = ? OR name IN ([?]) OR id IN ([]) AND created = @',
		'users', 'name', 'Trevor', ['Tom', 'Dick', 'Harry'], [1, 2, 3], 'NOW()');
```
No need for escaping, no quotes, no array handling and no concatenations ...

Output:
```sql
SELECT * FROM users WHERE name = "Trevor" OR name IN ("Tom", "Dick", "Harry") OR id IN (1, 2, 3) AND created = NOW()
```


## Description

SQLQB is essentially just **a glorified string wrapper** targeting SQL query strings with multiple ways to do the same thing, depending on your personal preference or coding style (supports multiple naming conventions, has camelCase and snake_case function names, or you can write statements in the constructor). Designed to be 100% Multibyte-capable (**UTF-8**, depending on your [mb_internal_encoding()](http://php.net/manual/en/function.mb-internal-encoding.php) setting, all functions use mb\_\* internally), **supports ANY database** (database connection is optional, it's just a string concatenator, write the query for your database/driver your own way) and **supports ANY framework** (no framework required or external dependencies), **light-weight** (one variable) but **feature rich**, **stateless** (doesn't know anything about the query, doesn't parse or validate the query), write in **native SQL language** with **zero learning curve** (only knowledge of SQL syntax) and functionality that is targeted to **rapidly write, design, test, build, develop and prototype** raw/native SQL query strings. You can build **entire SQL queries** or **partial SQL fragments** or even **non-SQL strings**.

## History

I got the initial inspiration for this code when reading about the [MyBatis SQL Builder Class](http://www.mybatis.org/mybatis-3/statement-builders.html); and it's dedicated to the few; but proud developers that love the power and flexibility of writing native SQL queries! With great power ...

It was originally designed to bridge the gap between ORM query builders and native SQL queries; by making use of a familiar ORM-style '**[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)**', but keeping the syntax as close to SQL as possible.


### Speed and Safety

This library is not designed for speed of execution or to be 100000% safe from SQL injections, it WILL however do a better job than manually escaping strings yourself; but only real 'prepared statements' offer protection from SQL injections; however they add a lot more complexity and many restrictions. In reality, it's almost impossible to write an entire website using only real/true prepared statements, so you'll invariably have to write many 'unprepared' statements; and that is where this class can help you; by writing safer 'unprepared' statements! It will 'quote' and 'escape' strings, detect the correct data type to use; but it doesn't do syntax checking, syntax parsing, query/syntax validation etc. The main task is to replace your placeholders with the corresponding data, with the ability to auto-detect the data type.

### To simplify the complex

This class isn't particularly useful or necessary for small/static queries like `'SELECT * FROM users WHERE id = ' . $id;`

But it really starts to shine when your SQL query gets larger and more complex; really shining on `INSERT` and `UPDATE` queries. The larger the query, the greater the benefit; that is what it was designed to do. All the complexity and tedious work of 'escaping', 'quoting' and concatenating strings is eliminated by simply putting `?` where you want the variable, this library takes care of the rest.

So when you find yourself dealing with '[object-relational impedance mismatch](https://en.wikipedia.org/wiki/Object-relational_impedance_mismatch)'; because you have a database of 400+ tables, 6000+ columns/fields, one table with 156 data fields, 10 tables with over 100 fields, 24 tables with over 50 fields, 1000+ varchar/char fields as I have; just remember this library was designed to help reduce some of that complexity! Especially still having (semi-)readable queries when you come back to them in a few months or years makes it a joy to use.

### Limitations of 'real' prepared statements

One of the limitations is that you cannot do this: `WHERE ? = ?` which you can in this class, another limitation is that you basically cannot use NULL values (there are workarounds). Also, you cannot use dynamic column/table/field names, such as `SELECT ? FROM ?`, all of which you can with this class; anything you can do in your `$db->query($sql)` you can do here!

## Install

Composer
```
composer require twister/sql
```
manually
```json
/* composer.json */
	"require": {
		"php": ">=5.6",
		"twister/sql": "*"
	}
```
or from GIT
```
https://github.com/twister-php/sql
```

Requirements (similar to Laravel):
```
PHP 5.6+ (for ...$args syntax)
Multibyte mb_* extension
```


### Hello World

```php
echo sql('Hello @', 'World');
```
```
Hello World
```

```php
echo sql('Hello ?', 'World');
```
```
Hello "World"
```

### Hello SQL World

```php
echo sql('SELECT ?, ?, ?, @', 1, "2", 'Hello World', 'NOW()');
```
```
SELECT 1, 2, "Hello World", NOW()
```
Note: 'numeric' values are not quoted (even when they are in strings)

#### More Examples

```php
echo sql('?, ?, ?, ?, ?, ?, ?', 4, '5', "Trevor's", 'NOW()', true, false, null);
```
```
4, 5, "Trevor\'s", "NOW()", 1, 0, NULL, 
```
"NOW()" is an SQL function that will not be executed, use `@` for raw output strings

```php
echo sql('@, @, @, @, @, @, @', 4, "5", "Trevor's", 'NOW()', true, false, null);
```
```
4, 5, Trevor's, NOW(), 1, 0, NULL
```
"Trevor's" is not escaped with `@` and will produce an SQL error


### Fluent Style

```php
echo sql()->select('u.id', 'u.name', 'a.*')
          ->from('users u')
            ->leftJoin('accounts a ON a.user_id = u.id AND a.overdraft >= ?', 5000)
          ->where('a.account = ? OR u.name = ? OR a.id IN ([])', 'BST002', 'foobar', [1, 2, 3])
          ->orderBy('u.name DESC')
	  ->limit(5, 10);
```
```sql
SELECT u.id, u.name, a.*
FROM users u
  LEFT JOIN accounts a ON a.user_id = u.id AND a.overdraft >= 5000
WHERE a.account = "BST002" OR u.name = "foobar" OR a.id IN (1, 2, 3)
ORDER BY u.name DESC
LIMIT 5, 10
```
Queries include additional whitespace for formatting and display purposes, which can be removed by calling `Sql::singleLineStatements()`. SQL keywords can be made lower-case by calling `Sql::lowerCaseStatements()`


### Other features

#### Arrays:

```php
echo sql('WHERE id IN ([])', [1, 2, 3]);
```
```sql
WHERE id IN (1, 2, 3)
```

```php
echo sql('WHERE name IN ([?])', ['joe', 'john', 'james']);
```
```sql
WHERE name IN ("joe", "john", "james")
```

```php
echo sql('WHERE id = :id OR name = :name OR dob = :dob:raw', ['id' => 5, 'name' => 'Trevor', 'dob' => 'NOW()']);
```
```sql
WHERE id = 5 OR name = "Trevor" OR dob = NOW()
```

#### Range:

```php
echo sql('WHERE id IN (1..?) OR id IN (?..?)', 3, 6, 8);
```
```sql
WHERE id IN (1, 2, 3) OR id IN (6, 7, 8)
```

#### Text filters:

eg. trim, pack (merge internal whitespace) & crop to 20 characters

```php
echo sql('SET description = %s:pack:trim:crop:20', "Hello     World's   Greatest");
```
```sql
SET description = "Hello World\'s Greate"
```

# Beginners guide

There are two main ways to write your queries; either use the constructor like an `sprintf` function (eg. `sql('?', $value)`), or use the '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)' (method chaining) by calling `sql()->select(...)->from(...)->where(...)` etc.

### fluent interface

The general idea of is very simple; when you call a function, it essentially just appends the function/statement name (eg. `select(...)`, `from(...)`, `where(...)`) (with some extra whitespace) to the internal `$sql` string variable, and returns `$this` for method chaining.

#### pseudo-code

```php
class Sql
{
	function select(...$cols)
	{
		$this->sql .= 'SELECT ' . implode(', ', $cols);
    		return $this;
	}
	function from(...$tables)
	{
		$this->sql .= PHP_EOL . 'FROM ' . implode(', ', $tables);
		return $this;
	}
	function leftJoin($stmt, ...$params)
	{
		return $this->prepare(PHP_EOL . 'LEFT JOIN ', ...$params);
	}
	function where($stmt, ...$params)
	{
		return $this->prepare(PHP_EOL . 'WHERE ', ...$params);
	}
	function prepare($stmt, ...$params)
	{
		//	the magic happens here
		//	processing `?`, `@`, escaping, quoting etc.
	}
}
```
```php
echo sql()->select('*')
          ->from('users u')
	    ->leftJoin('accounts a ON a.user_id = u.id')
	  ->where('u.id = ?', 5);
```
```sql
SELECT *
FROM users u
LEFT JOIN accounts a ON a.user_id = u.id
WHERE u.id = 5
```

Some functions like `leftJoin` and `where` support the `prepare`/`sprintf` style with variable args, while other like the `select` and `from` are more conveniently coded to just `implode` your values.

# Multiple calling conventions

The code supports camelCase, snake_case and UPPER_CASE syntax; as well as a short form syntax:


### Constructor

```php
use Twister;

$sql = new sql();
$sql = new Sql();
$sql = new SQL();

// or

$sql = new \Twister\Sql();
```

### Convenient `sql()` function

```php
function sql($stmt = null, ...$params)
{
	return new Twister\Sql($stmt, ...$params);
}

$sql = sql();
$sql = Sql();
$sql = SQL();
```

### camelCase

```php
->select('col1', 'col2', 'col3')
->from('table t')
->join('table2 t2 ON ... = ?', $var)
->leftJoin('table3 t3 ON ... = ?', $var)
->where('foo = ?', 'bar')
->groupBy('t.col1', 't2.col2')
->orderBy('t.col1 DESC')
->limit(5, 10);

// other common functions

->selectDistinct(..)
->insert(..)
->insertInto(..)
->values(..)
->set(..)
->delete(..)
->deleteFrom(..)
->having(..)
->union(..)
```

### snake_case

```php
->select('col1', 'col2', 'col3')
->from('table t')
->join('table2 t2 ON ... = ?', $var)
->left_join('table3 t3 ON ... = ?', $var)
->where('foo = ?', 'bar')
->group_by('t.col1', 't2.col2')
->order_by('t.col1 DESC')
->limit(5, 10);

// other common functions

->select_distinct(..)
->insert(..)
->insert_into(..)
->values(..)
->set(..)
->delete(..)
->delete_from(..)
->having(..)
->union(..)
```

### UPPER_CASE

```php
->SELECT('col1', 'col2', 'col3')
->FROM('table t')
->JOIN('table2 t2 ON ... = ?', $var)
->LEFT_JOIN('table3 t3 ON ... = ?', $var)
->WHERE('foo = ?', 'bar')
->GROUP_BY('t.col1', 't2.col2')
->ORDER_BY('t.col1 DESC')
->LIMIT(5, 10);

// other common functions

->SELECT_DISTINCT(..)
->INSERT(..)
->INSERT_INTO(..)
->VALUES(..)
->SET(..)
->DELETE(..)
->DELETE_FROM(..)
->HAVING(..)
->UNION(..)
```

### short syntax

```php
->s('col1', 'col2', 'col3')		//	s  = SELECT
->f('table t')				//	f  = FROM
->j('table2 t2 ON ... = ?', $var)	//	j  = JOIN
->lj('table3 t3 ON ...?', $var)         //	lj = LEFT JOIN
->w('foo = ?', 'bar')			//	w  = WHERE
->gb('t.col1', 't2.col2')		//	gb = GROUP BY
->ob('t.col1 DESC')			//	ob = ORDER BY
->l(5, 10);				//	l  = LIMIT

// other common functions

->sd(..)				//	sd = SELECT DISTINCT
->i(..)					//	i  = INSERT
->ii(..)				//	ii = INSERT INTO
->v(..)					//	v  = VALUES
->d(..)					//	d  = DELETE
->df(..)				//	df = DELETE FROM
->h(..)					//	h  = HAVING
```

# Addendum

## Setting the connection

The connection only needs to be set ONCE for ALL the `sql()` objects you create. You do NOT need a new connection, you just give your normal connection object to the class; and it will extract what it needs and build an internal 'driver'. The connection is stored in a static class variable, so ALL instances of the class share the same connection.

The connection type is automatically detected: either a PDO, MySQLi or SQLite3 object, or PostgreSQL/MySQL resource connection.

It will be necessary to set the connection to take full advantage of all the features offered by the class.

```php
\Twister\Sql::setConnection($conn);
```

Once the connection is set, the class (and all the `sql()` instances you create afterwards) will use your connection to 'escape' and 'quote' strings, and you have the ability to execute your queries directly from the class if you want. Executing queries with the class is entirely optional, but very convenient!

### query(), exec(), fetchAll(), lookup()

There are 4 very light-weight functions you can call directly from the `sql` object. All connection types have been unified.

#### fetchAll()

```php
$array = sql('SELECT ...')->fetchAll();
```

Returns an array containing all of the result set rows as an associated array.

Based on [`PDOStatement::fetchAll`](http://php.net/manual/en/pdostatement.fetchall.php)

PDO code sample
```php
function ($sql) use ($conn)
{
	$recset = $conn->query($sql);
	if ( ! $recset) {
		throw new \Exception('PSO::query() error: ' . $conn->errorInfo()[2]);
	}
	$result = $recset->fetchAll(\PDO::FETCH_ASSOC);
	$recset->closeCursor();
	return $result;
};
```

#### lookup()

```php
$value = sql('SELECT 999')->lookup();
echo $value;
```
```php
999
```

```php
$data = sql('SELECT 1 AS id, "Trevor" AS name')->lookup();
var_dump($data);
```
```php
array(2) {
  ["id"]   => string(1) "1"
  ["name"] => string(6) "Trevor"
}
```

`lookup()` will return a single row of data, or a single value depending on how many columns you select. If you select one column, you will get a value directly in $value or a null. If you selected several columns, they will be returned as an associative array, where the keys are the column names. Only the first row is returned, any other results will be discarded.

This function works similar to [SQLite3::querySingle](http://php.net/manual/en/sqlite3.querysingle.php), except the result 'mode' is auto-detected, which corresponds to the `$entire_row` value in `SQLite3::querySingle`

#### query()

```php
$recset = sql(...)->query();
```

`query()` will execute the SQL query with the `query()` function of your database connection, returning the same result. This is a very thin wrapper, making it extremely fast and convenient to use; it's much more convenient than getting the connection object from a dependency injection/IoC container like `$container->db()->query($sql);`, it's just `sql(..)->query()`

#### exec()

```php
$affected_rows = sql('DELETE FROM ...')->exec();
```

`exec()` executes an SQL query which you do not expect a 'query' result. This is usually an `INSERT`, `UPDATE` or `DELETE` statement. The `affected rows` value is returned on MySQL and SQLite3, and is the same as returned from `PDO::exec()`.

This function calls `PDO::exec()`, `MySQLi->real_query()` and `SQLite3::exec()` internally depending on your connection type.


## Literal ? and @

PDO will support `??` as a literal `?` in future editions; as proposed by the PDO standard for PHP 7.2 [here](https://wiki.php.net/rfc/pdo_escape_placeholders)

This class also supports `??` for a literal `?` in your code, as well as `@@` and `%%` for literal `@` and `%`


# Features:

* One single file: no other classes, interfaces, traits or custom exceptions
* 5,000+ lines of code (including comments) with full documentation
* Powerful 200 character Multibyte regular expression powers the replacement engine ([mb_ereg_replace_callback()](http://php.net/manual/en/function.mb-ereg-replace-callback.php) with a 600 line function)
* ORM style '[Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)'
* Intends to bridge the gap between the '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)' of ORM's and raw/native SQL statements
* Queries are built in natural SQL string concatenation order, just appending to the internal `$sql` string variable
* Any query with any complexity and any number of custom commands can be expressed through SQLQB.
* PHP 5.6+ (makes extensive use of the [...$arg syntax](http://php.net/manual/en/functions.arguments.php#functions.variable-arg-list.new))
* No external dependencies except (mb\_\*) extension. Use SQLQB in any PHP application or framework.
* Multiple function call / code styles supported, SELECT() or select(), leftJoin(), left_join() or LEFT_JOIN()
* Simple global wrapper function `$sql = sql();` instead of calling `$sql = new Twister\Sql();`
* Makes extensive use of PHP Magic Methods (\_\_toString(), \_\_get(), \_\_invoke(), \_\_call())
* Adds a small amount of additional whitespace to format your queries for display
* Minimal SQL abstraction
* Completely database neutral and agnostic; but PDO, MySQLi, PostgreSQL and SQLite are the primary targets.
* Built-in drivers for PDO, MySQLi, PostgreSQL, SQLite and MySQL (old). The driver is embedded, not separate classes.

## What it doesn't do:

* does NOT parse your string
* does NOT validate your string
* does NOT verify your string
* does NOT error check the syntax
* does NOT try to abstract raw/native SQL from you, just gives you the tools to write it faster and safer
* does NOT try to replace writing raw/native SQL
* does NOT re-order or change the natural order of SQL statements
* does NOT change the name, meaning or intention of traditional SQL statements
* does NOT use reflection or annotations
* does NOT re-structure/re-format/re-align/re-arrange your statement (except adding some whitespace for readability)
* does NOT do input/parameter validation/verification, other than string escaping
* does NOT check that column types match the database schema
* does NOT use any schema/model/entity/mapping/config/YAML/XML/temporary/cache files
* does NOT store an abstract SQL statement interface internally, everything it builds is visible
* does NOT have any outside dependencies, only ONE single file, PHP 5.6+ and mb\_\*
* does NOT add any other classes (except Sql), no interfaces, no traits, no new exception classes etc.
* does NOT guarantee your string/query is safe from SQL injections (like true 'prepared statements'); **However**, it's still a lot safer than writing raw/native/traditional SQL strings


# Conclusion

My goal is to enable you (and me) to write SQL queries faster, safer and more readable than old-school concatenations.

They might not execute faster, especially when the regular expression engine kicks in, but the amount of time you will save, and coming back to your code in a few months or years later and immediately being able to read and understand it is invaluable! Code readability should come first in most situations, especially large queries/projects.

I believe this code and solution is unique; as I haven't found anything like it before; there simply are NO other libraries out there with the same capabilities and feature set; and very few help you write raw SQL queries faster.

I hope you enjoy this effort, it has taken me many weeks (hundreds of hours) of my free time to write this code and documentation.

I'd love to hear from anyone else making use of the code! Features, suggestions, praise, questions, comments and thanks are welcome!

Trevor out...
