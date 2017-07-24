# SQL
Raw SQL Query Builder ~ the Swiss-army knife of raw SQL queries

## Introduction

We already have some great tools when working with managed or abstracted database layers like ORM's and Doctrine DBAL. And most ORM's allow you to write and execute raw SQL queries when you require greater/custom flexibility or functionality they don't provide.

However, what tools do you have when working with the plain text strings of raw/native SQL queries? You have lots of string concatenations, [`implode()`](http://php.net/manual/en/function.implode.php), [`PDO::prepare`](http://php.net/manual/en/pdo.prepare.php), [`PDO::quote`](http://php.net/manual/en/pdo.quote.php), [`sprintf`](http://php.net/manual/en/function.sprintf.php) (for the brave) and [`mysqli::real_escape_string`](http://php.net/manual/en/mysqli.real-escape-string.php) because the first version wasn't real enough or the name long enough.

## The One Ring to rule them all, One Ring to bind them

Introducing the '[Raw SQL Query Builder](https://github.com/twister-php/sql)'; combining all the functionality of having placeholders like `?`, `:id`, `%s`, `%d`; with an ORM style '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)' (methods `return $this` for method-chaining) and much more.

It's the glue that sits between `$sql = '...';` and `$db->query($sql)`. The part where you have to concatenate, 'escape', 'quote', 'prepare' and 'bind' values in a raw SQL query string.

This is not an ORM or replacement for an ORM, it's the tool you use when you need to create a raw SQL query string with the convenience of placeholders. It doesn't 'prepare' or 'execute' your queries exactly like `PDO::prepare` does; but it does support the familiar syntax of using `?` or `:id` as placeholders. It also supports a subset of `sprintf`'s `%s` / `%d` syntax.

In addition, it supports inserting 'raw' strings (without quotes or escapes) with `@`; eg. `sql('dated = @', 'NOW()')`, even replacing column or table names as well auto-`implode()` arrays with `[]` eg. `sql('WHERE id IN ([])', $array')`


```php
echo sql('SELECT * FROM @ WHERE @ = ? OR name IN ([?]) OR id IN ([])',
	'users', 'name', 'Trevor', ['Tom', 'Dick', 'Harry'], [1, 2, 3]);
```
No escaping, no quotes, no array hanling/imploding and no concatenations ...

Output:
```sql
SELECT * FROM users WHERE name = "Trevor" OR name IN ("Tom", "Dick", "Harry") OR id IN (1, 2, 3)
```


## Description

Raw SQL Query Builder is essentially just **a glorified string wrapper** with countless ways to do the same thing (supports multiple naming conventions, both snake_case and camelCase function names). Designed to be 100% Multibyte-aware (**UTF-8**, depending on your mb\_\* extention, all functions use mb\_\* internally), **supports ALL databases** (no database connection used, write the query for your database/driver) and **ALL frameworks** (no framework or external dependencies), **light-weight** (one variable) but **feature rich**, **stateless** (doesn't know anything about the query, doesn't parse or validate the query), write in **native SQL language** with **zero learning curve** (only knowledge of SQL syntax) and functionality that is targeted to **rapidly write, design, test, build, develop and prototype** raw/native SQL query strings. You can build **entire SQL queries** or **partial SQL fragments** or even **non-SQL strings**.

## History

I got the initial inspiration for this code when reading about the [MyBatis SQL Builder Class](http://www.mybatis.org/mybatis-3/statement-builders.html); and it's dedicated to the few; but proud developers that love the power and flexibility of writing native SQL queries! With great power ...

It was originally designed to bridge the gap between ORM query builders and native SQL queries; by making use of a familiar ORM-style '**[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)**', but keeping the syntax as close to SQL as possible.

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
echo sql('SELECT * FROM users WHERE id = ?', "5");
```
```
SELECT * FROM users WHERE id = 5
```
`is_numeric()` values are not escaped


```php
echo sql('SELECT * FROM users WHERE name = ?', "Trevor's");
```
```
SELECT * FROM users WHERE name = "Trevor\'s"
```
Exactly the same escaping rules as [`mysqli::real_escape_string`](http://php.net/manual/en/mysqli.real-escape-string.php)



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
          ->orderBy('u.name DESC');
```
```sql
SELECT u.id, u.name, a.*
FROM users u
  LEFT JOIN accounts a ON a.user_id = u.id AND a.overdraft >= 5000
WHERE a.account = "BST002" OR u.name = "foobar" OR a.id IN (1, 2, 3)
ORDER BY u.name DESC
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
WHERE id IN ("joe", "john", "james")
```

```php
echo sql('WHERE id = :id OR name = :name OR dob = :dob:raw AND failure = ?', ['id' => 5, 'name' => 'Trevor', 'dob' => 'NOW()'], 'not an option');
```
```sql
WHERE id = 5 OR name = "Trevor" OR dob = NOW() AND failure = "not an option"
```

#### Range:

```php
echo sql('WHERE id IN (1..?) OR id IN (?..?)', 3, 6, 8);
```
```sql
WHERE id IN (1, 2, 3) OR id IN (6, 7, 8)
```

#### Text filters:

eg. pack (merge internal whitespace) & trim

```php
echo sql('SET description = %s:pack:trim:20', "Hello     World's   Greatest");
```
```sql
SET description = "Hello World\'s Greate"
```


### Speed and Safety

This library is not designed for speed of execution or to be 100% safe from SQL injection, that task is left up to you. It will 'quote' and 'escape' your strings, but it doesn't 'manage' the query or connection, it doesn't do syntax checking, syntax parsing, query/syntax validation etc. It doesn't even have a database connection, it just concatenates strings with convenient placeholders that auto-detect the data type.

### To simplify the complex

It's not particularly useful or necessary for small/static queries like `'SELECT * FROM users WHERE id = ' . $id;`

This library really starts to shine when your SQL query gets larger and more complex; really shining on `INSERT` and `UPDATE` queries. The larger the query, the greater the benfit; that is what it was designed to do, to simplify the complexity of medium to large queries; all that complexity of 'escaping' and 'quoting' strings is eliminated by simply putting `?` where you want the variable, this library takes care of the rest.

So when you find yourself dealing with a database of 400+ tables, 6000+ columns/fields, one table with 156 data fields, 10 tables with over 100 fields, 24 tables with over 50 fields, 1000+ varchar/char fields; or '[object-relational impedance mismatch](https://en.wikipedia.org/wiki/Object-relational_impedance_mismatch)' becomes a problem in your ORM; or you need to write custom queries against some or all of this data, then you will truly realise how much time and stress this library can save you.

# A taste of things to come



```php
$sql = SQL('?', '"Hello World"');	//	?  quoted + escaped
// '"\"Hello World\""'

$sql = Sql('?', 4);			//	?  auto-detects numeric, null and string
// '4'

$sql = sql('?', null);
// 'NULL'
```

```php
$sql = SQL('@', '"Hello World"');	//	@  literals

// '"Hello World"'


$sql = Sql('@', 'Hello World');		//	@  no quotes or escaping

// 'Hello World'


$sql = sql('@', 'CURDATE()');		//	@  useful for function calls

// 'CURDATE()'
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

echo sql()->select('*')
          ->from('users u')
	  ->where('u.id = ?', 5);
SELECT * FROM users u WHERE u.id = 5
```


# Multiple calling conventions

The code supports camelCase, snake_case and UPPER_CASE syntax; as well as short form syntax:


### Constructor

```php
use Twister;

$sql = new sql();
$sql = new Sql();
$sql = new SQL();
```

### sql() helper/wrapper function

```php
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








