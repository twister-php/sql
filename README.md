# SQL
Raw SQL Query Builder ~ the swiss-army knife of raw SQL queries

## Introduction

We already have some great tools when working with managed or abstracted database layers like ORM's and Doctrine DBAL. And most ORM's allow you to write and execute raw SQL queries when you require greater/custom flexibility or functionality they don't provide.

However, what tools do you have when working with the plain text strings of raw/native SQL queries? You have lots of string concatenations, [`implode()`](http://php.net/manual/en/function.implode.php), [`PDO::prepare`](http://php.net/manual/en/pdo.prepare.php), [`PDO::quote`](http://php.net/manual/en/pdo.quote.php), [`sprintf`](http://php.net/manual/en/function.sprintf.php) (for the brave) and [`mysqli::real_escape_string`](http://php.net/manual/en/mysqli.real-escape-string.php) because the first version wasn't real enough or the name long enough.

## The One Ring to rule them all, One Ring to bind them

Introducing the '[Raw SQL Query Builder](https://github.com/twister-php/sql)'; combining all the functionality of having placeholders like `?`, `:id`, `%s`, `%d`; with an ORM style '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)' and much more.

It's the glue that sits between `$sql = '...';` and `$db->query($sql)`. The part where you have to concatenate, 'escape', 'quote', 'prepare' and 'bind' values in a raw SQL query string.

This is not an ORM or replacement for an ORM, it's the tool you use when you need to create a raw SQL query string with the convenience of placeholders. It doesn't 'prepare' or 'execute' your queries exactly like `PDO::prepare` does; but it does support the familiar syntax of using `?` or `:id` as placeholders. It also supports a subset of `sprintf`'s `%s` / `%d` syntax.

In addition, it supports inserting 'raw' strings (without quotes or escapes) with `@`; eg. `sql('dated = @', 'NOW()')`, and it can auto-implode arrays with `[]` eg. `sql('WHERE id IN ([])', $array')`

### Speed and Safety

This library is not designed for speed of execution or to be 100% safe from SQL injection, that task is left up to you. It will 'quote' and 'escape' your strings, but it doesn't 'manage' the query, it doesn't do syntax checking, syntax parsing, query/syntax validation etc. In fact, it doesn't even need or use a database connection.

### Simplify the Complex

It's not particularly useful or necessary for small/static queries like `'SELECT * FROM users WHERE id = ' . $id;`

This library really starts to shine when your SQL query gets larger and more complex; really shining on `INSERT` and `UPDATE` queries. The larger the query, the greater the benfit; that is what it was designed to do, to simplify the complexity of medium to large queries.

So when you find yourself dealing with a database of 400+ tables, 6000+ columns/fields, one table with 156 data fields, 10 tables with over 100 fields, 24 tables with over 50 fields, 1000+ varchar/char fields; and '[object-relational impedance mismatch](https://en.wikipedia.org/wiki/Object-relational_impedance_mismatch)' in your ORM becomes a problem or you need to write custom queries against some or all of this data, then you will truly realise how much time and headaches this library can save you.

## A taste of things to come

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



### sprintf() limitations

`sprintf` doesn't automatically quote or escape your strings, thus making it 

### PDO::prepare

`[PDO::prepare](http://php.net/manual/en/pdo.prepare.php)` is possibly the _best_ known tool to use in the industry for protecting your scripts from SQL injection. However, it has many limitations because it's based on an underlying concept of 'preparing' a statement to be executed multiple times, the underlying database is responsible for constructing a query plan for multiple executions. This limitation doesn't allow you to change field/column names, tables or more dynamic 'WHERE' clauses.

My experience of 'preparing', 'binding' (optional) and 'executing' statements for multiple execution, has very limited use in realworld applications. It's more work than it's worth, because less than 5% of my queries are executed in a loop. Most queries are executed once off, so the only real benefit left is the protection from SQL injection.

This library does NOT 'prepare' your statements, it simply returns a string like `sprintf`; and therefore allows ANYTHING to be changed, column, field or table names. Anything that can be replaced in a string like `sprintf` with `%s` 




This class purely allows you to create a plain text string with the placeholders


You don't need layers of 'abstraction' (where you can't even see the query being generated) to build safer or better SQL.
Most ORM's 'abstract' the SQL commands away from you to 'make it safer', or 'more portable', or 'faster/better to write'.
They just introduce hundreds of new/weird functions (like Laravel's `take()` instead of `limit()`), new librabries, hundreds of new classes, interfaces, traits, exceptions etc.

It's the difference between "managed" and "unmanaged" code.

Well, I like my 
to make working with SQL strings easier, safer and faster; you need the right set of functionality!
When we work with strings, we have functions like strlen(), strpos(), substr() etc. so we don't have to write those ourselves.
When it comes to functions that help us write an SQL query, we only have the standard string functions to work with.
This wrapper doesn't 
