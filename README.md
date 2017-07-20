WORK in active progress! As of 20 July 2017, probably for the next week or so!
This is the unfinished README! Many things have changed and improved since I wrote this a few days ago!

# SQL
SQL Query String Wrapper ~ the swiss-army knife of native SQL queries; because (SQL-language) abstraction is for the weak!

SQL String Wrapper (or SQL Query Builder) is a **stateless**, **native SQL language** (no learning curve) **query string builder** or wrapper; with functionality designed to help you **rapidly write, design, build, develop and prototype** native SQL query strings. Write queries YOUR way with as much or as little of the functionality you require. It's the glue that sits between `$sql = SQL();` and `$db->query($sql)` (the part where you might want to concatenate, `escape`, filter, validate, verify, `bind`, `prepare` eg. `?, ?, %md5, @, ?, %clamp:1:10, %int, %s, %d`). Both **framework and database neutral** and agnostic (you don't even need a connection), it's **light-weight** (only the `$sql` variable is stored internally) but **feature rich**. You can use it to build **partial SQL fragments** or even non-SQL strings (eg. `$str = SQL('%s:raw:crop:trim:pack:80', $str);`); it's basically just **a glorified (SQL) string concatenator**.

But using a powerful built-in Multibyte (UTF-8) string/value regex-parser (custom-written and usage is optional); allows you to mix familiar `sprintf()`-like syntax `%s`/`%d` with `prepare`'s placeholder `?` or an `@` placeholder for NOT escaping/raw values like function calls; and a unique blend of powerful text and integer transforms like `%clamp:1:10`, range testing `%int:1:10`, accepting nullable fields `%s:n:80:crop` (**n**ull or string), hashing values `%md5`/`%sha1`/`%sha256`, JSON encoding, text transforms `%text:lcase:ucase:ucwords:ucfirst:crop:trim:pack:nullable:800`). `%s, %char, %varchar, %string, %text` are all synonyms (except `%text` provides additional transforms); it's basically just **a glorified (SQL) string concatenator**, leveraging your existing SQL knowledge so there is virtually no learning curve. It's also designed to bridge the gap with ORM developers by making use of a familiar ORM-style '**[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)**'. It's the swiss-army knife of SQL queries and it's glorious!

In short, this is just a wrapper around an internal empty `$sql` string variable (so empty that it even starts as a null value).
You can do as much or as little as you want with it, like build string fragments with the powerful `prepare()` engine and join it to other strings. Do sprintf()-like `%s`, or PDO::prepare() 

https://packagist.org/packages/willoucom/php-sql-query
https://packagist.org/packages/atk4/dsql

Welcome to the Ultimate raw/native SQL Builder Class ever developed for PHP.

# History

This class was inspired by the [MyBatis SQL Builder Class](http://www.mybatis.org/mybatis-3/statement-builders.html), and is dedicated to the few; but proud developers that love the power and flexibility of writing raw/native SQL queries! But with great power ...

The ultimate goal of this class is to bridge the gap between the '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)' of ORM's and raw/native SQL statements; bringing even more power and flexibility than you thought possible!


## Example
```php
$sql = 'SELECT COUNT(*) FROM users WHERE id = ' . $id . ' OR name = ' . $db->quote($name);
```
```php
$sql = SQL('SELECT COUNT(*) FROM users WHERE id = ? OR name = ?', $id, $name);
```

### Multiple ways to write the same statements
```php
$sql = SQL('SELECT COUNT(*) FROM users')->WHERE('id = ? OR name = ?', $id, $name);
// or
$sql = SQL()->SAF('users WHERE id = ? OR name = ?', $id, $name);		// short form: (S)ELECT (A)LL (F)ROM
// or
$sql = SQL()->SAF->users->where('id = ? OR name = ?', $id, $name);		// SAF is a dynamic property, uppercase only
// or
$sql = SQL()->SELECT_ALL_FROM('users WHERE id = ? OR name = ?', $id, $name);	// long form
// or
$sql = SQL()->Select('COUNT(*)')->From('users')->Where('id = ? OR name = ?', $id, $name);
// or
$sql = SQL()->select('COUNT(*)')->from('users')->where('id = ? OR name = ?', $id, $name);
```
### [fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)
```php
$sql = SQL()
         ->SELECT_ALL_FROM('users')
         ->WHERE('age > ?', $age)
         ->ORDER_BY('age DESC');
// or
$sql = SQL()->SELECT_ALL_FROM('users'SELECT COUNT(*) FROM users');
         ->WHERE('name = ?', $id, $name)
         ->ORDER

```


What you do with that string, how you construct it, is up to you; it helps you as much or as little as you want. You can build a whole statement, or just a query fragment, or you can use it to escape only the key-value/column-value pairs of INSERT/UPDATE statements.

Basically, this class helps you as much or as little as you want it to! The biggest benefit I can see, even for hard-core SQL developers will be the ability to escape a key-value array pair of column-values for large queries



So what happens when you find yourself working with 400+ tables, 6000+ columns, 156 columns in one table, 10 tables with over 100 columns, 24 tables with over 50 columns, 1000+ varchar/char columns ... both ORM and raw SQL become a nightmare. ORM's suffer from [object-relational impedance mismatch](https://en.wikipedia.org/wiki/Object-relational_impedance_mismatch); creating an abstraction layer with a built in query interface over PDO, which is already an abstraction layer and doesn't support many . I believe that the abstraction layer provided by most ORM's are actually harmful


## Install

Composer
```
composer require twister/sql
```
Composer manually
```json
/* composer.json */
	"require": {
		"php": ">=5.4",
		"twister/sql": "*"
	}
```
GIT
```
https://github.com/twister-php/sql
```




## Examples

```php
// Style 1
$sql = SQL()->SELECT_ALL->FROM->users;

// Style 2
$sql = SQL()->SELECT('COUNT(*)')->FROM('users');

// Style 3
$sql = SQL()->SA->F->users;

// Raw Style
$sql = 'SELECT COUNT(*) FROM users';


$id = 1;
$name = "Trevor's Home"

$sql = SQL()->INSERT_INTO('users', ['id' => $id, 'name' => $name]);
$sql = 'SELECT COUNT(*) FROM users';

```

https://en.wikipedia.org/wiki/Fluent_interface


Because when you find yourself dealing with a database of 400+ tables and 6000+ columns, there will come a time when an ORM Eloquent or Doctrine just doesn't provide you with the required functionality and you are forced to write raw queries  the problem/pain in raw query string building is 'value escaping'!


### Features:

* This is IT! The ULTIMATE raw/native/natural language SQL Query Builder for PHP
* ORM style '[Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)'
* Intends to bridge the gap between the '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)' of ORM's and raw/native SQL statements
* Natural SQL language - queries are built in natural SQL string concatenation order, just appending to the internal `$sql` string variable. No new keywords to learn, leverage your existing SQL knowledge
* Any driver: execute queries against any driver that accepts natural SQL commands: PDO, MySQLi, pg\_\*, SQLLite etc.
* Queries are built in natural SQL string concatenation order, just appending to the internal `$sql` string variable
* Any Query - any query with any complexity can be expressed through SQLQB.
* One single file: no other classes, interfaces, traits or custom exceptions
* No dependencies except PHP 5.6 and (mb\_\*) extention. Use SQLQB in any PHP application or framework.
* Multiple function call / code styles suported, SELECT() or select()
* Global wrapper function for the truly lazy: `$sql = SQL()` instead of `$sql = new SQL()`
* No namespace! Yes, this is a feature! Because I don't want to write `new \Twister\SQL();` (I'm lazy); and because it has NO equal!
* Database connection is optional but recommended. Without a database connection (set with `SQL::setConn($conn);`), the internal 'escape' functions use `addslashes` internally, but when a MySQLi connection is used, will use [`mysqli::real_escape_string`](http://php.net/manual/en/mysqli.real-escape-string.php), or [`PDO::quote`](http://php.net/manual/en/pdo.quote.php)
* Access to the internal SQL string at any time with `(string) $sql`
* 1400 lines of PHP code where written on day one.
* Makes extensive use of PHP Magic Methods (\_\_toString(), \_\_get(), \_\_invoke(), \_\_call())
* Dynamic properties become SQL statements: eg. `$sql = SQL()->SELECT_ALL;` = `$sql = 'SELECT COUNT(*)';`
* Adds a small amount of additional whitespace to format your string: eg. `$sql = SQL()->SELECT_ALL->FROM->users`
* completely database agnostic; but MySQL, PDO, Postgres and SQLite are the primary targets.
* PHP 5.6+ (for the [...$arg syntax](http://php.net/manual/en/functions.arguments.php#functions.variable-arg-list.new))

### What it does:

* builds an internal `$sql` string value with concatenations
* Allows you to unleash the FULL potential of your database (anything is valid!)
* supports declaring a 'raw' string, which is NOT parsed/escaped with '@' as first character in the column name
  - Example: `->VALUES(['@ my id' => '@id', '@ created' => 'NOW()', '@' => '"Not escaped 1"', '@2' => "'Not escaped 2'", 'No column name', ' @ ' => '@ not first', '@ 4', '@ first, another value, no escape error' ])`
  - Output: `VALUES(@id, NOW(), "Not escaped 1", 'Not escaped 2', "No column name", "@ not first", @ first, another value, no escape error )`
  - Note that in `->VALUES()` statements, the array keys are NOT used for output, only to check for `@`; so anything unique starting with `@` is acceptable to prevent the string from being escaped, you can even have one of the keys be only the `@` value!
  - Will strip the '@' sign from column names in all `->INSERT`, `->INSERT_INTO` and `->INTO()` statements
* uses a [Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface) to provide an ORM-like syntax
* will 'escape' your data with the following statements:
  - `$sql = SQL()->e("Trevor's");` -> `$sql = '"Trevor\'s"'`;
  - `$sql = SQL()->VALUES("Trevor's");` -> `$sql = 'VALUES("Trevor\'s")'`;
* executing statements is optional
* Minimal SQL abstraction
* Adds minimal whitespacing, for readability purposes when you dump/print/echo/display/log the statement
* Can build just a single fragment
* Minimal use of \Exceptions, only when it doesn't know what to do with your data type/object



### What is doesn't do:

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
* does NOT try to abstract raw/native SQL from you
* does NOT try to replace writing all raw/native SQL
* does NOT re-order or change the natural order of SQL statements
* does NOT change the name or meaning of traditional SQL statements (eg. `->LIMIT(10)` is `->take(10)` in Eloquent)
* does NOT use reflection or annotations
* does NOT re-structure/re-format/re-align/re-arrange your statement
* does NOT do input/parameter validation/verification, other than simple string escaping
* does NOT check that column types match the database schema
* does NOT use any schema/model/entity/mapping/config/YAML/XML/temporary/cache files
* does NOT store an abstract SQL statement interface internally, everything it builds is visible
* does NOT have any outside dependencies, only ONE single file and PHP 5.6+
* does NOT add any other classes (except SQL), NO Interfaces, NO Traits, NO Exception classes etc.



FAQ
====
Q: If you love writing native/raw SQL queries so much, why this project?
A:
* Mainly because the biggest pain in the ass is value escaping for large INSERT/UPDATE statements, concatenating them all to this long INSERT/UPDATE statement with `PDO::quote` or `mysqli::real_escape_string` is a nightmare. The largest table I have, has 156 columns/fields, I have 10x tables with over 100 fields, and 24 tables with over 50 columns.

* Because when you find yourself dealing with a database of 400+ tables and 6000+ columns, there will come a time when an ORM Eloquent or Doctrine just doesn't provide you with the required functionality and you are forced to write raw queries  the problem/pain in raw query string building is 'value escaping'! This is also one of the biggest arguments I've heard against raw/native SQL, is that it's 'not secure', or '


## prepare()

### Validation and Formatting Rules

Prepare combines ideas from 'prepared' statements, form validation rules, sprintf(), .NET and python. The replacement is done with the mb_ereg_replace_callback() from the multibyte (`mb_`) extention
