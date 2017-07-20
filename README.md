WORK in active progress! As of 20 July 2017, probably for the next week or so!
This is the unfinished README! Many things have changed and improved since I wrote this a few days ago!

# SQL
SQL Query String Wrapper ~ the swiss-army knife of native SQL queries; because (SQL-language) abstraction is for the weak!

### Description

SQL String Wrapper (or SQL Query Builder) is a **database and framework neutral**, **light-weight** but **feature rich**, **stateless**, **native SQL language query string builder/wrapper**; with **no learning curve** (only knowledge of SQL syntax) and functionality that is targeted to **rapidly write, design, build, develop and prototype** native SQL query strings. You can build **partial SQL fragments** or non-SQL strings. It's basically just **a glorified string concatenator** with about 10 ways to do/write the same thing.

It's the glue that sits between `$sql = SQL();` and `$db->query($sql)` (the part where you might want to concatenate, `escape`, filter, validate, verify, `bind` or `prepare` a statement.

The powerful built-in Multibyte (UTF-8) string/value regex-parser (custom-written, usage is optional); allows you to mix familiar `sprintf()`-like syntax `%s`/`%d` with `prepare`'s placeholder `?` or an `@` placeholder for NOT escaping/raw values like function calls; and a unique blend of powerful text and integer transforms like `%clamp:1:10`, range testing `%int:1:10`, accepting nullable fields `%s:n:80:crop` (**n**ull or string), hashing values `%md5`/`%sha1`/`%sha256`, JSON encoding, text transforms `%text:lcase:ucase:ucwords:ucfirst:crop:trim:pack:nullable:800`). `%s, %char, %varchar, %string, %text` are all synonyms (except `%text` provides additional transforms);

It's also designed to bridge the gap between ORM developers and native SQL queries; by making use of a familiar ORM-style '**[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)**'.

In short, this is just a wrapper around an internal empty `$sql` string variable (so empty that it even starts as a null value).
You can do as much or as little as you want with it, like build string fragments with the powerful `prepare()` engine and join it to other strings. Do sprintf()-like `%s`, or PDO::prepare() 

https://packagist.org/packages/willoucom/php-sql-query
https://packagist.org/packages/atk4/dsql

Welcome to the Ultimate raw/native SQL Builder Class ever developed for PHP.


## Install

Composer
```
composer require twister/sql
```
manually
```json
/* composer.json */
	"require": {
		"php": ">=5.4",
		"twister/sql": "*"
	}
```
or from GIT
```
https://github.com/twister-php/sql
```

## Beginners guide

Internally, the basic idea is very simple. When you call a function, it just appends the function name (eg. `SELECT(...)`, `FROM(...)`, `WHERE(...)`) (with some extra whitespace) to the internal `$sql` string variable, and returns `$this` for method chaining AKA a '[fluent interface](https://en.wikipedia.org/wiki/Fluent_interface)'

eg. simplified pseudo-code
```php
function SELECT($str)
{
    $this->sql .= 'SELECT ' . $str;
    return $this;
}
function FROM($str)
{
    $this->sql .= PHP_EOL . 'FROM ' . $str;
    return $this;
}

$sql = SQL()->SELECT('*')->FROM('users');
// $sql = 'SELECT * FROM users;
```

### Constructor

The constructor also accepts ANY string value (can be any string, a fragment, or even non-SQL code):

```php
$sql = SQL('SELECT * FROM users');
// $sql = 'SELECT * FROM users;

$sql = SQL('Hello World');
```

However, almost ALL the functions (including the constructor) work much like `sprintf()` / `PDO::prepare()`!

```php
$sql = SQL('WHERE id = ? OR name = ? OR fname = %s OR lname = %varchar', $id, $name, $fname, ...);

// More advanced:

$sql = SQL('WHERE id = %d OR name = %s OR role = "@" OR DOB = @', $id, $name, 'admin', '"2017-01-01"');
```


### Syntax sugar

There is even more majic to come, this is just a small taste!

```php
$sql = SQL()->SELECT_ALL_FROM			//	special dynamic properties, also: SAF = 'SELECT * FROM '
            ->users                             //	another different kind of dynamic (unknown) property
	      ->J('accounts ...')		//	hundreds of shortcuts, S (select), F (from), J (join), LJ, W, OB, GB, L
	    ->WHERE('id = ?, $id)		//	easier to read, because the text and variable are together
	      ->OR('name = ?, $name)
	      ->OR('role = "@"', $role)		//	$role might be an Enum or internal string like 'admin'
	      ->OR('created = @', 'CURDATE()')	//	values with @ are NOT escaped
	      ->OR('price = %d', $price)	//	sprintf() style
	      ->OR('fname = %', $fname)
	      ->AND('lname = %s:pack:trim', $lname)	//	many internal modifiers and text transformations
	      ->OR('special = %clamp:1:10', $value)	//	integer clamped min(max()) style
	      ->AND('(this AND that) OR (this OR that) OR whatever GROUP BY name LIMIT (5)');
```

NO function call requires you to ONLY put values of that type in it, you can finish your SQL query from anywhere, any function, any time. The function call syntax just looks better, and easier to break up and format your query. There is NO syntax checking, it's just a string, you are free to do whatever you want in it!



By design, it looks almost like writing normal SQL; so you might be wondering what makes it special? This is just the beginners guide, it's like writing `|\d+|` in your first Regular Expression, you might wonder what makes Regular Expressions so powerful.

so this:
```php
$sql = 'SELECT id, name FROM users WHERE id = ' . $id . ' OR name = ' . $db->quote();
```
is basically the same as this:
```php
$sql = SQL()->SELECT('id, name')
            ->FROM('users')
	    ->WHERE('id = ? OR name = ?', $id, $name);
```

pseudo-internal code
```php
function SELECT($str)
{
    $this->sql .= 'SELECT ' . $str;
    return $this;
}
function FROM($str)
{
    $this->sql .= PHP_EOL . 'FROM ' . $str;
    return $this;
}
function WHERE($str, ...$args)
{
    return $this->prepare(PHP_EOL . 'WHERE ' . $str, ...$args);
}
```

Actually, the SELECT(), FROM(), WHERE(), JOIN(), LEFT_JOIN() etc. methods ALL accept variable input (PHP 5.6+ syntax).
`prepare()` is essentially the same as `sprintf()` + `PDO::prepare()` ... but uses custom code whith MANY options not only ?, %s, %d etc.


Start just by wrapping some existing queries inside the constructor and play around.

```php
$sql = SQL($sql);
```
or
```php
$sql = SQL('SELECT * FROM users');
```

Most of the core functionality is exactly like writing a normal statement, it's just doing string concatenations.

If you don't like that style, you can use any style you like (function names are NOT case-sensitive, and functions like `LEFT_JOIN` have versions without \_, eg. leftJoin()):

```php
$sql = SQL()
       ->select('u.id, u.name')				//	or SELECT(), Select(), SeLeCt()
       ->from('users u')				//	or FROM(), From(), fRoM()
         ->leftJoin('accounts a ON a.user_id = u.id')	//	or LEFT_JOIN(), LeftJoin(), Left_Join(), LeFtJoIn()
       ->where('u.id = ? OR u.name = ?', $id, $name);	//	or WHERE(), Where(), ->wHeR()
```

Then see what features it offers for what you want to do.


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
$sql = SQL()->SAF('users WHERE id = ? OR name = ?', $id, $name);	// short form: (S)ELECT (A)LL (F)ROM
// or
$sql = SQL()->SAF->users
	    ->where('id = ? OR name = ?', $id, $name);		// SAF = dynamic property, (UC only)
// or
$sql = SQL()->SELECT_ALL_FROM('users WHERE id = ? OR name = ?', $id, $name);	// long form
```
### Multiple ways to write the same statements
```php
// or
$sql = SQL()->seLEct('COUNT(*)')		//	function names are NOT case sensitive!
            ->fRoM('users u')
            ->JOIN('accounts a ON user.id = a.user_id')
            ->JOIN['accounts a ON user.id = a.user_id'] // array syntax allows you to add raw strings anywhere
            ->rightJoin('...')[' -- bla bla comment ']
            ->jOIn('accounts a ON user.id = %d', 123)	//	sprintf-like syntax
            ->LEFT_JOIN('...')
            ->leftJoin('...')			//	both left_join and leftJoin style syntax supported!
            ->leFt_JOin('...')
            ->JOIN->users			//	JOIN is a dynamic property, must be uppercase!
            ->_ON_['user.id = a.user_id']	//	special `array` syntax supported anywhere for raw text
	    ->_OR_['user.id = 123']		//	dynamic properties return $this, allowing the chain
            ->wHeRe('id = ? OR name = ?', $id, $name)
            ->OR('name = %s:trim:pack', $name)	//	text transformations
            ->oRderBy('id')			//	ORDER_BY / OrDeR_By / orderBy / oRdErBy / OrderBy
	    ->O('id')				//	S, F, J, LJ, W, O, L are all short for SELECT, FROM, WHERE, LIMIT etc.
	    ->O->id				//	same as above, `O` and `id` are dynamic properties here
            ->liMit(5);
```


What you do with that string, how you construct it, is up to you; it helps you as much or as little as you want. You can build a whole statement, or just a query fragment, or you can use it to escape only the key-value/column-value pairs of INSERT/UPDATE statements.

Basically, this class helps you as much or as little as you want it to! The biggest benefit I can see, even for hard-core SQL developers will be the ability to escape a key-value array pair of column-values for large queries



So what happens when you find yourself working with 400+ tables, 6000+ columns, 156 columns in one table, 10 tables with over 100 columns, 24 tables with over 50 columns, 1000+ varchar/char columns ... both ORM and raw SQL become a nightmare. ORM's suffer from [object-relational impedance mismatch](https://en.wikipedia.org/wiki/Object-relational_impedance_mismatch); creating an abstraction layer with a built in query interface over PDO, which is already an abstraction layer and doesn't support many . I believe that the abstraction layer provided by most ORM's are actually harmful





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
