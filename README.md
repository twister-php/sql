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
No need for escaping, no quotes, no array hanling and no concatenations ...

Output:
```sql
SELECT * FROM users WHERE name = "Trevor" OR name IN ("Tom", "Dick", "Harry") OR id IN (1, 2, 3) AND created = NOW()
```


## Description

SQLQB is essentially just **a glorified string wrapper** targeting SQL with countless ways to do the same thing (supports multiple naming conventions, has camelCase and snake_case function names, or you can write statements in the constructor). Designed to be 100% Multibyte-capable (**UTF-8**, depending on your [mb_internal_encoding()](http://php.net/manual/en/function.mb-internal-encoding.php) setting, all functions use mb\_\* internally), **supports ANY database** (no database connection is used, it's just a string concatenator, write the query for your database/driver your own way) and **supports ANY framework** (no framework required or external dependencies), **light-weight** (one variable) but **feature rich**, **stateless** (doesn't know anything about the query, doesn't parse or validate the query), write in **native SQL language** with **zero learning curve** (only knowledge of SQL syntax) and functionality that is targeted to **rapidly write, design, test, build, develop and prototype** raw/native SQL query strings. You can build **entire SQL queries** or **partial SQL fragments** or even **non-SQL strings**.

## History

I got the initial inspiration for this code when reading about the [MyBatis SQL Builder Class](http://www.mybatis.org/mybatis-3/statement-builders.html); and it's dedicated to the few; but proud developers that love the power and flexibility of writing native SQL queries! With great power ...

It was originally designed to bridge the gap between ORM query builders and native SQL queries; by making use of a familiar ORM-style '**[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)**', but keeping the syntax as close to SQL as possible.


### Speed and Safety

This library is not designed for speed of execution or to be 100% safe from SQL injection, that task is left up to you. It will 'quote' and 'escape' your strings, but it doesn't 'manage' the query or connection, it doesn't do syntax checking, syntax parsing, query/syntax validation etc. It doesn't even have a database connection, it just concatenates strings with convenient placeholders that auto-detect the data type.

### To simplify the complex

It's not particularly useful or necessary for small/static queries like `'SELECT * FROM users WHERE id = ' . $id;`

This library really starts to shine when your SQL query gets larger and more complex; really shining on `INSERT` and `UPDATE` queries. The larger the query, the greater the benfit; that is what it was designed to do, to simplify the complexity of medium to large queries; all that complexity of 'escaping', 'quoting' and concatenating strings is eliminated by simply putting `?` where you want the variable, this library takes care of the rest.

So when you find yourself dealing with '[object-relational impedance mismatch](https://en.wikipedia.org/wiki/Object-relational_impedance_mismatch)'; because you have a database of 400+ tables, 6000+ columns/fields, one table with 156 data fields, 10 tables with over 100 fields, 24 tables with over 50 fields, 1000+ varchar/char fields; just remember this library was designed to help reduce some of that complexity! Especially still having (semi-)readable queries when you come back to them in a few months or years.


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
Multibyte mb_* extention
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

Exactly the same escaping rules as [`mysqli::real_escape_string`](http://php.net/manual/en/mysqli.real-escape-string.php) apply.

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

The general idea is very simple; when you call a function, it basically just appends the function name (eg. `select(...)`, `from(...)`, `where(...)`) (with some extra whitespace) to the internal `$sql` string variable, and returns `$this` for method chaining AKA a '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)'

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
	  ->where('u.id = ?', 5);
```
```sql
SELECT * FROM users u WHERE u.id = 5
```

# Multiple calling conventions

The code supports camelCase, snake_case and UPPER_CASE syntax; as well as a short form syntax:


### Constructor

```php
use Twister;

$sql = new sql();
$sql = new Sql();
$sql = new SQL();
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

// others

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

// more

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

// more

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


// others

->sd(..)				//	sd = SELECT DISTINCT
->i(..)					//	i  = INSERT
->ii(..)				//	ii = INSERT INTO
->v(..)					//	v  = VALUES
->d(..)					//	d  = DELETE
->df(..)				//	df = DELETE FROM
->h(..)					//	h  = HAVING
```


# Features:

* One single file: no other classes, interfaces, traits or custom exceptions
* 4,000+ lines of code and full documentation
* Powerful 200 character Multibyte regular expression powers the replacement engine ([mb_ereg_replace_callback()](http://php.net/manual/en/function.mb-ereg-replace-callback.php) with a 600 line function)
* ORM style '[Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)'
* Intends to bridge the gap between the '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)' of ORM's and raw/native SQL statements
* Any driver: execute queries against any driver that accepts natural SQL commands: PDO, MySQLi, pg\_\*, SQLLite etc.
* Queries are built in natural SQL string concatenation order, just appending to the internal `$sql` string variable
* Any query with any complexity and any number of custom commands can be expressed through SQLQB.
* PHP 5.6+ (makes extensive use of the [...$arg syntax](http://php.net/manual/en/functions.arguments.php#functions.variable-arg-list.new))
* No external dependencies except (mb\_\*) extention. Use SQLQB in any PHP application or framework.
* Multiple function call / code styles supported, SELECT() or select(), leftJoin(), left_join() or LEFT_JOIN()
* Global wrapper function `$sql = sql();` instead of calling `$sql = new Twister\Sql();`
* Makes extensive use of PHP Magic Methods (\_\_toString(), \_\_get(), \_\_invoke(), \_\_call())
* Adds a small amount of additional whitespace to format your queries for display
* Completely database neutral and agnostic; but MySQL, PDO, Postgres and SQLite are the primary targets.
* Minimal SQL abstraction

## What it doesn't do:

* does NOT parse your string
* does NOT validate your string
* does NOT verify your string
* does NOT error check the syntax
* does NOT build valid SQL statements for you
* does NOT guarantee your string/query is safe from SQL injections
* does NOT protect you from the big bad wolf called SQL injections
* does NOT hold your hand or make coffee
* does NOT treat SQL like an abomination
* does NOT treat you like an SQL child
* does NOT try to abstract raw/native SQL from you, just gives you the tools to write it faster
* does NOT try to replace writing raw/native SQL
* does NOT re-order or change the natural order of SQL statements
* does NOT change the name or meaning of traditional SQL statements (eg. `->limit(10)` is `->take(10)` in Eloquent)
* does NOT use reflection or annotations
* does NOT re-structure/re-format/re-align/re-arrange your statement
* does NOT do input/parameter validation/verification, other than simple string escaping
* does NOT check that column types match the database schema
* does NOT use any schema/model/entity/mapping/config/YAML/XML/temporary/cache files
* does NOT store an abstract SQL statement interface internally, everything it builds is visible
* does NOT have any outside dependencies, only ONE single file, PHP 5.6+ and mb\_\*
* does NOT add any other classes (except Sql), no interfaces, no traits, no new exception classes etc.


# Conclusion

My goal is to enable you (and me) to write SQL queries faster, safer and more readable than old-school concatenations.

They might not execute faster, especially when the regular expression engine kicks in, but the amount of time you will save, and coming back to your code in a few months or years later and immediately being able to read and understand it is invaluable! Code readability should come first in most situations, especially large queries.

I believe this code and solution is unique; as I haven't found anything like it before; there simply are NO other libraries out there with the same capabilities and feature set; and very few help you write raw SQL queries faster.

I hope you enjoy this effort, it has taken me many weeks (hundreds of hours) of my free time to write this code and documentation.

I'd love to hear from anyone else making use of the code! Features, suggestions, praise, questions, comments and thanks are welcome!

Trevor out...
