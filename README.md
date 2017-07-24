# SQL
Raw SQL Query Builder ~ the swiss-army knife of raw SQL queries

## Introduction

We already have some great tools when working with managed or abstracted database layers like ORM's and Doctrine DBAL. And most ORM's allow you to write and execute raw SQL queries when you require greater/custom flexibility or functionality they don't provide.

However, what tools do you have when working with the plain text strings of raw/native SQL queries? You have lots of string concatenations, [`implode()`](http://php.net/manual/en/function.implode.php), [`PDO::prepare`](http://php.net/manual/en/pdo.prepare.php), [`PDO::quote`](http://php.net/manual/en/pdo.quote.php), [`sprintf`](http://php.net/manual/en/function.sprintf.php) for the brave, [`mysqli::real_escape_string`](http://php.net/manual/en/mysqli.real-escape-string.php) because the first version wasn't real enough or the name long enough.

## The One Ring to rule them all, One Ring to bind them

Introducing the '[Raw SQL Query Builder](https://github.com/twister-php/sql)'; combining all that functionality and more.

It's the glue that sits between `$sql = '...';` and `$db->query($sql)`. The part where you have to concatenate, 'escape', 'prepare' and 'bind' values in a raw plain text SQL query.

This is not an ORM or replacement for an ORM, it's the tool you use when you have to create a raw SQL query string. It doesn't 'prepare' or 'execute' your queries like [`PDO::prepare`](http://php.net/manual/en/pdo.prepare.php) does, but it does help you build the raw SQL string like `sprintf` with `%s` / `%d` or [`PDO::prepare`](http://php.net/manual/en/pdo.prepare.php) with `?` or `:id` placeholders; but safer than `sprintf` and easier and more flexible than [`PDO::prepare`](http://php.net/manual/en/pdo.prepare.php) (eg. `PDO::prepare` doesn't allow dynamic table, column, field names or dynamic `JOIN` / `WHERE` clauses).

It allows anything that can be changed in text with `sprintf`; column, field or table names, dynamic `WHERE` statements, dynamic table joins etc. This library just allows you to add a powerful 'replacement' engine on your raw SQL queries, that combines 'prepare', 'quote', 'escape', 'sanitize', 'clamp' integers, range checks etc.

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
