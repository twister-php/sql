# SQL
Raw SQL Query String Builder ~ because abstraction is for the weak!

This class was inspired by the [MyBatis SQL Builder Class](http://www.mybatis.org/mybatis-3/statement-builders.html), and is dedicated to the few; but proud developers that love the power and flexibility of writing native/raw SQL queries! But with great power ...

In short, this is just a wrapper around an internal `$sql` string value, that just concatenates/appends statements/values/fragments.

This project is intended to bridge the gap between most ORM's; and the plain strings of raw SQL statements. Bringing the ['Fluent interface'](https://en.wikipedia.org/wiki/Fluent_interface) style to raw SQL query building.

So what happens when you find yourself working with 400+ tables, 6000+ columns, 156 columns in one table, 10 tables with over 100 columns, 24 tables with over 50 columns, 1000+ varchar/char columns ... both ORM and raw SQL become a nightmare. ORM's suffer from [object-relational impedance mismatch](https://en.wikipedia.org/wiki/Object-relational_impedance_mismatch); creating an abstraction layer with a built in query interface over PDO, which is already an abstraction layer and doesn't support many . I believe that the abstraction layer provided by most ORM's are actually harmful


## Install

```txt
composer require twister/sql
```
OR
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


Features:
* NO dependencies ~ except PHP 5.6+ (for the [...$arg syntax](http://php.net/manual/en/functions.arguments.php#functions.variable-arg-list.new))
* Fluent Functions just append to the internal $sql string variable
* [Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface): `$sql = SQL()->SELECT_ALL->FROM->users->WHERE->id->IS->NULL;` = `$sql = 'SELECT COUNT(*) FROM users';`
* Multiple Code Styles: the MyBatis and common SQL uppercase style `SELECT()` or lowercase `select()` or Laravel style `SQL()->table('users')->find()->get();`
* Global wrapper function for the truly lazy: `$sql = SQL()` instead of `$sql = new SQL()`; because I don't want to write `new \Twister\SQL();`, the SQL class has NO namespace, because it has NO equal!
* Database connection is optional but recommended. Without a database connection (set with `SQL::setConn($conn);`), the internal 'escape' functions use `addslashes` internally, but when a MySQLi connection is used, will use [`mysqli::real_escape_string`](http://php.net/manual/en/mysqli.real-escape-string.php), or [`PDO::quote`](http://php.net/manual/en/pdo.quote.php)
* Access to the internal SQL string at any time with `(string) $sql`
* 1400 lines of PHP code where written on day one.
* Makes extensive use of PHP Magic Methods (\_\_toString(), \_\_get(), \_\_invoke(), \_\_call())
* Dynamic properties become SQL statements: eg. `$sql = SQL()->SELECT_ALL;` = `$sql = 'SELECT COUNT(*)';`
* Adds a small amount of additional whitespace to format your string: eg. `$sql = SQL()->SELECT_ALL->FROM->users`

### What it does:

* - builds an internal `$sql` string value with concatenations
* - supports declaring a 'raw' string, which is NOT parsed/escaped with '@' as first character in the column name
  * Example: `->VALUES(['@ my id' => '@id', '@ created' => 'NOW()', '@' => '"Not escaped 1"', '@2' => "'Not escaped 2'", 'No column name', ' @ ' => '@ not first', '@ 4', '@ first, another value, no escape error' ])`
  * Output: `VALUES(@id, NOW(), "Not escaped 1", 'Not escaped 2', "No column name", "@ not first", @ first, another value, no escape error )`
  * Note that in `->VALUES()` statements, the array keys are NOT used for output, only to check for `@`; so anything unique starting with `@` is acceptable to prevent the string from being escaped, you can even have one of the keys be only the `@` value!
  * Will strip the '@' sign from column names in all `->INSERT`, `->INSERT_INTO` and `->INTO()` statements
* - uses a [Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface) to provide an ORM-like syntax
* - will 'escape' your data with the following statements:
  * `$sql = SQL()->e("Trevor's");` -> `$sql = '"Trevor\'s"'`;
  * `$sql = SQL()->VALUES("Trevor's");` -> `$sql = 'VALUES("Trevor\'s")'`;
* - executing statements is optional
* - Minimal SQL abstraction
* - Adds minimal whitespacing, for readability purposes when you dump/print/echo/display/log the statement



### What is doesn't do:

* - does NOT parse your string
* - does NOT validate your string
* - does NOT verify your string
* - does NOT guarantee your string/query is safe from SQL injections
* - does NOT protect you from the big bad wolf called SQL injections
* - does NOT expect you to parse/escape EVERY STRING in the world! (eg. `->VALUES(['@my_enum'=>'""'])` == '@'string is NOT escaped
* - does NOT hold your hand or make coffee
* - does NOT treat SQL like an abomination
* - does NOT re-order or change the natural order of SQL statements (except for some Laravel/Eloquent/Doctrine compatible statements)
* - does NOT change the name or meaning of statements (eg. `->LIMIT(10)` in Eloquent is `->take(10)`, except when implementing these)
* - does NOT use reflection or annotations
* - does NOT re-structure or re-format (tidy) your query statement (except when adding some PHP_EOL for display/echo purposes)
* - does NOT build valid SQL statements, the power is in YOUR hands to build the statements, the class just appends what you want
* - does NOT do input/parameter validation/verification, other than simple string escaping
* - does NOT check that column types match the database schema
* - does NOT use any schema/model/entity/mapping/config/YAML/XML/temporary/cache files
* - does NOT store an abstract SQL statement interface internally, everything it builds is visible
* - does NOT have any outside dependencies, only ONE single file and PHP 5.6+



FAQ
====
Q: If you love writing native/raw SQL queries so much, why this project?
A:
* Mainly because the biggest pain in the ass is value escaping for large INSERT/UPDATE statements, concatenating them all to this long INSERT/UPDATE statement with `PDO::quote` or `mysqli::real_escape_string` is a nightmare. The largest table I have, has 156 columns/fields, I have 10x tables with over 100 fields, and 24 tables with over 50 columns.

* Because when you find yourself dealing with a database of 400+ tables and 6000+ columns, there will come a time when an ORM Eloquent or Doctrine just doesn't provide you with the required functionality and you are forced to write raw queries  the problem/pain in raw query string building is 'value escaping'! This is also one of the biggest arguments I've heard against raw/native SQL, is that it's 'not secure', or '
