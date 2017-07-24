# SQL
Raw SQL Query Builder ~ the Swiss-army knife of raw SQL queries

## Introduction

We already have some great tools when working with managed or abstracted database layers like ORM's and Doctrine DBAL. And most ORM's allow you to write and execute raw SQL queries when you require greater/custom flexibility or functionality they don't provide.

However, what tools do you have when working with the plain text strings of raw/native SQL queries? You have lots of string concatenations, [`implode()`](http://php.net/manual/en/function.implode.php), [`PDO::prepare`](http://php.net/manual/en/pdo.prepare.php), [`PDO::quote`](http://php.net/manual/en/pdo.quote.php), [`sprintf`](http://php.net/manual/en/function.sprintf.php) (for the brave) and [`mysqli::real_escape_string`](http://php.net/manual/en/mysqli.real-escape-string.php) because the first version wasn't real enough or the name long enough.

## The One Ring to rule them all, One Ring to bind them

Introducing the '[Raw SQL Query Builder](https://github.com/twister-php/sql)'; combining all the functionality of having placeholders like `?`, `:id`, `%s`, `%d`; with an ORM style '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)' (methods `return $this` for method-chaining) and much more.

It's the glue that sits between `$sql = '...';` and `$db->query($sql)`. The part where you have to concatenate, 'escape', 'quote', 'prepare' and 'bind' values in a raw SQL query string.

This is not an ORM or replacement for an ORM, it's the tool you use when you need to create a raw SQL query string with the convenience of placeholders. It doesn't 'prepare' or 'execute' your queries exactly like `PDO::prepare` does; but it does support the familiar syntax of using `?` or `:id` as placeholders. It also supports a subset of `sprintf`'s `%s` / `%d` syntax.

In addition, it supports inserting 'raw' strings (without quotes or escapes) with `@`; eg. `sql('dated = @', 'NOW()')`, even replaing column names eg. `sql('WHERE @ = ?', 'name', 'Trevor')` becomes `WHERE name = "Trevor"`; and it can auto-implode arrays with `[]` eg. `sql('WHERE id IN ([])', $array')`

## Description

Raw SQL Query Builder is essentially just **a glorified string wrapper** with countless ways to do the same thing (supports multiple naming conventions, both snake_case and camelCase function names). It **supports ALL databases** (no database connection used, write the query for your database/driver) and **ALL frameworks** (no framework or external dependencies), **light-weight** (one variable) but **feature rich**, **stateless** (doesn't know or care about what part of the query you are working on), write in **native SQL language** with **zero learning curve** (only knowledge of SQL syntax) and functionality that is targeted to **rapidly write, design, test, build, develop and prototype** raw/native SQL query strings. You can build **entire SQL queries** or **partial SQL fragments** or even **non-SQL strings**.

### Speed and Safety

This library is not designed for speed of execution or to be 100% safe from SQL injection, that task is left up to you. It will 'quote' and 'escape' your strings, but it doesn't 'manage' the query or connection, it doesn't do syntax checking, syntax parsing, query/syntax validation etc. It doesn't even have a database connection, it just concatenates strings with convenient placeholders that auto-detect the data type.

### Simplify the Complex

It's not particularly useful or necessary for small/static queries like `'SELECT * FROM users WHERE id = ' . $id;`

This library really starts to shine when your SQL query gets larger and more complex; really shining on `INSERT` and `UPDATE` queries. The larger the query, the greater the benfit; that is what it was designed to do, to simplify the complexity of medium to large queries; all that complexity of 'escaping' and 'quoting' strings is eliminated by simply putting `?` where you want the variable, this library takes care of the rest.

So when you find yourself dealing with a database of 400+ tables, 6000+ columns/fields, one table with 156 data fields, 10 tables with over 100 fields, 24 tables with over 50 fields, 1000+ varchar/char fields; or '[object-relational impedance mismatch](https://en.wikipedia.org/wiki/Object-relational_impedance_mismatch)' becomes a problem in your ORM; or you need to write custom queries against some or all of this data, then you will truly realise how much time and stress this library can save you.

# A taste of things to come

## Hello World

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
