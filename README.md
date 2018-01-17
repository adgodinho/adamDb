AdamDb
======

AdamDb is a minimalist database query builder for PHP/ADOdb.
AdamDb is a fork from PicoDb by Frédéric Guillot

Features
--------

- Easy to use, easy to hack, fast and very lightweight
- Supported drivers: Sqlite, Mssql, Mysql, Postgresql
- Requires only ADOdb
- Use prepared statements
- License: MIT

Requirements
------------

- PHP >= 5.2
- ADOdb extension
- Sqlite, Mssql, Mysql or Postgresql

Documentation
-------------

### Installation

Add /lib/AdamDb to you project and call:

```bash
require_once dirname(__FILE__).'/lib/AdamDb/Database.php';
```

### Database connection

```php
require_once dirname(__FILE__).'/lib/AdamDb/Database.php';

$db = new Database($ADODB);
```

### INSERTION

```php
$array = array( 'column1' => 'value1', 
                'column2' => 'value2', 
                'column3' => 'value3', 
                'column4' => 'value4', 
                'column5' => 'value5');

$resultados = $ms->table('tableName')
  ->insert($array)
  ->execute();
```

or

```php
$pg->table('tableName')
  ->insert()
  ->column('column1', 'value1')
  ->column('column2', 'value2')
  ->column('column3', 'value3')
  ->column('column4', $pg->timestamp(), false)
  ->column('column5', 'value5')
  ->execute();
```

or

```php

$array = array( 'column1' => 'value1', 
                'column2' => 'value2', 
                'column3' => 'value3');

$pg->table('tableName')
  ->insert($array)
  ->column('column4', 'value4')
  ->column('column5', $pg->timestamp(), false)
  ->execute();
```

### Fetch LAST INSERTED ID

```php
$db->getLastId();
```

### TRANSACTIONS

```php
$db->transaction(function ($db) {
    $db->table('mytable')->save(array('column1' => 'foo'));
    $db->table('mytable')->save(array('column1' => 'bar'));
});
```

or

```php
$db->startTransaction();
// Do something...
$db->closeTransaction();

// Rollback
$db->cancelTransaction();
```

### FETCH ALL data

```php
$records = $db->table('mytable')->findAll();

foreach ($records as $record) {
    var_dump($record['column1']);
}
```

### Fetch all data as an associative array,  the first column becomes the key 

```php
$records = $db->table('mytable')->columns('column1', 'column2')->findAssoc();

```

### UPDATE

```php
$db->table('mytable')->where('id', 1)->save(array('column1' => 'hey'));
```

or

```php
$db->table('mytable')->where('id', 1)->update(array(('column1' => 'hey'));
```

### REMOVE records

```php
$db->table('mytable')->where('column1', 10, '<')->remove();
```

### SORTING

```php
$db->table('mytable')->asc('column1')->findAll();
```

or

```php
$db->table('mytable')->desc('column1')->findAll();
```

or

```php
$db->table('mytable')->orderBy('column1', 'ASC')->findAll();
```

Multiple sorting:

```php
$db->table('mytable')->asc('column1')->desc('column2')->findAll();
```

### LIMIT and OFFSET

```php
$db->table('mytable')->limit(10)->offset(5)->findAll();
```

### Fetch only SOME COLUMNS

```php
$db->table('mytable')->columns('column1', 'column2')->findAll();
```

### Fetch only ONE COLUMN

Many rows:

```php
$db->table('mytable')->findAllByColumn('column1');
```

One row:

```php
$db->table('mytable')->findOneColumn('column1');
```

### CAST (MSSQL AND POSTGRESQL ONLY)

```php
//Int 
$db->cast('column_1', 'int');

//Double 
$db->cast('column_1', 'double');

//Varchar 
$db->cast('column_1', 'varchar');

//Varchar(2)
$db->cast('column_1', 'varchar', '2');

//Date ISO varchar('YYYYMMDD')
$db->cast('column_1', 'date_iso');

//Date BR varchar('DD/MM/YYYY')
$db->cast('column_1', 'date_br');

//Date ISO date('YYYYMMDD')
$db->cast('column_1', 'to_date_iso');

//Date BR date('DD/MM/YYYY')
$db->cast('column_1', 'to_date_br');
```

### CURRENT DATE

```php
$db->date();
```

### CURRENT TIMESTAMP

```php
$db->timestamp();
```

## DATE DIFFERENCE (MSSQL AND POSTGRESQL ONLY)

```php
// Difference in years
$db->datediff('year', '2011-12-29 23:00:00', '2011-12-31 01:00:00');

// Difference in months
$db->datediff('month', '2011-12-29 23:00:00', '2011-12-31 01:00:00');

// Difference in weeks
$db->datediff('week', '2011-12-29 23:00:00', '2011-12-31 01:00:00');

// Difference in days
$db->datediff('day', '2011-12-29 23:00:00', '2011-12-31 01:00:00');

// Difference in hours'
$db->datediff('hour', '2011-12-29 23:00:00', '2011-12-31 01:00:00');

// Difference in minutes
$db->datediff('minute', '2011-12-29 23:00:00', '2011-12-31 01:00:00');

// Difference in seconds
$db->datediff('second', '2011-12-29 23:00:00', '2011-12-31 01:00:00');
```


### CUSTOM SELECT

```php
$db->table('mytable')->select(1)->where('id', 42)->findOne();
```

### SELECT SUBQUERY

```php
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');

$db->table('mytable')
       ->columns('column_5')
       ->addSubquery($subquery)
       ->findAll();

//or with alias

$db->table('mytable')
       ->columns('column_5')
       ->addSubquery($subquery, 'new_name')
       ->findAll();
```

### CASE

```php
$pg->table("mytable")
   ->beginCase()
   ->caseWhen()
   ->where('columnA', '10', '<')
   ->caseThen('0')
   ->caseElse('1')
   ->closeCase('result');
```

or

```php
$pg->table("mytable")
   ->beginCase()
   ->caseWhen()
   ->where('columnA', '10', '<')
   ->caseThen()
   ->addSubquery($subquery1)
   ->caseElse()
   ->addSubquery($subquery2)
   ->closeCase('result');
```

### DISTINCT

```php
$db->table('mytable')->distinct('columnA')->findOne();
```

### GROUP BY

```php
$db->table('mytable')->groupBy('columnA')->findAll();
```

### COUNT

```php
$db->table('mytable')->count();
```

### UNION

```php

$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');

$db->table('mytable')->columns('column2')->union($subquery)->findAll();
```

### SUM

```php
$db->table('mytable')->sum('columnB');
```

### SUM column values during update

Add the value 42 to the existing value of the column "mycolumn":

```php
$db->table('mytable')->sumColumn('mycolumn', 42)->update();
```

### INCREMENT column

Increment a column value in a single query:

```php
$db->table('mytable')->where('another_column', 42)->increment('my_column', 2);
```

### DECREMENT column

Decrement a column value in a single query:

```php
$db->table('mytable')->where('another_column', 42)->decrement('my_column', 1);
```

### EXISTS

Returns true if a record exists otherwise false.

```php
$db->table('mytable')->where('column1', 12)->exists();
```

### ROW_NUMBER

```php
\\SELECT ( ROW_NUMBER() OVER ( PARTITION BY colum1, column2 ORDER BY columnOrderBy DESC ) ) AS alias FROM personagens
$db->table("mytable")->rowNumber(array('colum1', 'column2'), 'columnOrderBy', 'DESC', 'alias')->findAll();

\\or

\\SELECT ( ROW_NUMBER() OVER ( ORDER BY columnOrderBy DESC ) ) AS alias FROM personagens
$db->table("mytable")->rowNumber(null, 'columnOrderBy', 'ASC', 'alias')->findAll();
````

### RANK

```php
\\SELECT ( RANK() OVER ( PARTITION BY colum1, column2 ORDER BY columnOrderBy DESC ) ) AS alias FROM personagens
$db->table("mytable")->rank(array('colum1', 'column2'), 'columnOrderBy', 'DESC', 'alias')->findAll();

\\or

\\SELECT ( RANK() OVER ( ORDER BY columnOrderBy DESC ) ) AS alias FROM personagens
$db->table("mytable")->rank(null, 'columnOrderBy', 'ASC', 'alias')->findAll();
````

### JOIN

```php
// SELECT * FROM mytable INNER JOIN my_other_table AS t1 ON t1.id=mytable.foreign_key
$db->table('mytable')->left('my_other_table as t1', 't1.id = mytable.foreign_key')->findAll();
```

or 

```php
// SELECT * FROM mytable LEFT JOIN my_other_table AS t1 ON t1.id=mytable.foreign_key
$db->table('mytable')->left('my_other_table as t1', 't1.id = mytable.foreign_key', 'left')->findAll();
```

or

```php
// SELECT * FROM mytable LEFT JOIN my_other_table AS t1 ON t1.id=mytable.foreign_key
$db->table('mytable')->left('my_other_table as t1', 't1.id = mytable.foreign_key', 'right')->findAll();
```

### WHERE condition

Prepared statement:

```php
$db->table('mytable')
   ->where('column1', 'hey') //Defaults to = operator
   ->where('column2', 'hey', '!=')
   ->where('column3', '10', '>')
   ->where('column4', '10', '>=')
   ->where('column5', '10', '<')
   ->where('column6', '10', '<=')
   ->findAll();

// or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');

$db->table('mytable')
   ->where($subquery)
   ->findAll();
```

Unprepared statement:

```php
$db->table('mytable')
   ->where('column1', 'column7') //Defaults to = operator
   ->where('column2', 'column8', '!=', 'false')
   ->where('column3', 'column9', '>', 'false')
   ->where('column4', 'column10', '>=', 'false')
   ->where('column5', 'column11', '<', 'false')
   ->where('column6', 'column12', '<=', 'false')
   ->findAll();
```

### OR WHERE condition 

Prepared statement:

```php
$db->table('mytable')
   ->where('column1', 'hey') //Defaults to = operator
   ->orWhere('column2', 'hey', '!=')
   ->orWhere('column3', '10', '>')
   ->orWhere('column4', '10', '>=')
   ->orWhere('column5', '10', '<')
   ->orWhere('column6', '10', '<=')
   ->findAll();

// or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3')->orWhere('column6', '10', '<=');

$db->table('mytable')
   ->where($subquery)
   ->findAll();
```

Unprepared statement:

```php
$db->table('mytable')
   ->where('column1', 'column7') //Defaults to = operator
   ->orWhere('column2', 'column8', '!=', 'false')
   ->orWhere('column3', 'column9', '>', 'false')
   ->orWhere('column4', 'column10', '>=', 'false')
   ->orWhere('column5', 'column11', '<', 'false')
   ->orWhere('column6', 'column12', '<=', 'false')
   ->findAll();
```

### IN condition

Prepared statement:

```php
$db->table('mytable')
       ->whereIn('column1', array('hey', 'bla'))
       ->findAll();

//or using a subquery

$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');

$db->table('mytable')
       ->columns('column_5')
       ->whereIn($subquery)
       ->findAll();
```

Unprepared statement:

```php
$db->table('mytable')
       ->whereIn('column1', array('column2', 'column2'), false)
       ->findAll();
```

### OR IN condition

Prepared statement:

```php
$db->table('mytable')
       ->whereIn('column1', array('hey', 'bla'))
       ->orWhereIn('column1', array('xxx', 'yyy'))
       ->findAll();

//or using a subquery

$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');
$subquery2 = $db->table('another_table')->columns('column5')->where('column8', 'value9');

$db->table('mytable')
       ->columns('column_5')
       ->whereIn($subquery)
       ->orWhereIn($subquery2)
       ->findAll();
```

Unprepared statement:

```php
$db->table('mytable')
       ->whereIn('column1', array('column2', 'column2'), false)
       ->orWhereIn('column1', array('column3', 'column3'), false)
       ->findAll();
```

### NOT IN condition

Prepared statement:

```php
$db->table('mytable')
       ->whereNotIn('column1', array('hey', 'bla'))
       ->findAll();

//or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');

$db->table('mytable')
       ->columns('column_5')
       ->whereNotIn($subquery)
       ->findAll();

```

Unprepared statement:

```php
$db->table('mytable')
       ->whereNotIn('column1', array('column2', 'column2'), false)
       ->findAll();
```

### OR NOT IN condition

Prepared statement:

```php
$db->table('mytable')
       ->whereIn('column1', array('hey', 'bla'))
       ->orWhereNotIn('column1', array('xxx', 'yyy'))
       ->findAll();

//or using a subquery

$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');
$subquery2 = $db->table('another_table')->columns('column5')->where('column8', 'value9');

$db->table('mytable')
       ->columns('column_5')
       ->whereIn($subquery)
       ->orWhereNotIn($subquery2)
       ->findAll();
```

Unprepared statement:

```php
$db->table('mytable')
       ->whereIn('column1', array('column2', 'column2'), false)
       ->orWhereNotIn('column1', array('column3', 'column3'), false)
       ->findAll();
```

### ILIKE condition

Not case-sensitive:

Prepared statement:

```php
$db->table('mytable')
   ->ilike('column1', '%foo%')
   ->findAll();

//or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');

$db->table('mytable')
   ->ilike($subquery)
   ->findAll();
```

Unprepared statement:

```php
$db->table('mytable')
   ->ilike('column1', 'column2', false)
   ->findAll();
```

### OR ILIKE condition

Not case-sensitive:

Prepared statement:

```php
$db->table('mytable')
   ->ilike('column1', '%foo%')
   ->orIlike('column1', '%abc%')
   ->findAll();

//or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');
$subquery2 = $db->table('another_table')->columns('column3')->where('column4', 'value4');

$db->table('mytable')
   ->ilike($subquery)
   ->orlike($subquery2)
   ->findAll();
```

Unprepared statement:

```php
$db->table('mytable')
   ->ilike('column1', 'column2', false)
   ->orIlike('column3', 'column4', false)
   ->findAll();
```

### NOT ILIKE condition

Not case-sensitive:

Prepared statement:

```php
$db->table('mytable')
   ->notIlike('column1', '%foo%')
   ->findAll();

//or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');

$db->table('mytable')
   ->notIlike($subquery)
   ->findAll();
```

Unprepared statement:

```php
$db->table('mytable')
   ->notIlike('column1', 'column2', false)
   ->findAll();
```

### OR NOT ILIKE condition

Not case-sensitive:

Prepared statement:

```php
$db->table('mytable')
   ->ilike('column1', '%foo%')
   ->orNotIlike('column1', '%abc%')
   ->findAll();

//or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');
$subquery2 = $db->table('another_table')->columns('column3')->where('column4', 'value4');

$db->table('mytable')
   ->ilike($subquery)
   ->orNotIlike($subquery2)
   ->findAll();
```

Unprepared statement:

```php
$db->table('mytable')
   ->ilike('column1', 'column2', false)
   ->orNotIlike('column3', 'column4', false)
   ->findAll();
```

### IS NULL condition

```php
$db->table('mytable')
   ->isNull('column1')
   ->findAll();

//or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');

$db->table('mytable')
   ->isNull($subquery)
   ->findAll();
```

### OR IS NULL condition

```php
$db->table('mytable')
   ->isNull('column1')
   ->orIsNull('column2')
   ->findAll();

//or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');
$subquery2 = $db->table('another_table')->columns('column4')->where('column5', 'value5');

$db->table('mytable')
   ->isNull($subquery)
   ->orIsNull($subquery2)
   ->findAll();
```

### IS NOT NULL condition

```php
$db->table('mytable')
   ->notNull('column1')
   ->findAll();
   
//or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');

$db->table('mytable')
   ->notNull($subquery)
   ->findAll();
```

### OR IS NOT NULL condition

```php
$db->table('mytable')
   ->notNull('column1')
   ->orNotNull('column2')
   ->findAll();
   
//or using a subquery
$subquery = $db->table('another_table')->columns('column2')->where('column3', 'value3');
$subquery2 = $db->table('another_table')->columns('column4')->where('column5', 'value5');

$db->table('mytable')
   ->notNull($subquery)
   ->orNotNull($subquery)
   ->findAll();
```


### Multiple conditions

How to make a subconditions inside `AND` condition:

```php
$db->table('mytable')
    ->like('column2', '%mytable')
    ->beginAnd()
    ->like('column2', '%mytable')
    ->orWhere('column1', 3, '>=')
    ->closeAnd()
    ->orWhere('column5', 'titi')
    ->findAll();
```

How to make a subconditions inside `OR` condition:

```php
$db->table('mytable')
    ->like('column2', '%mytable')
    ->beginOr()
    ->like('column2', '%mytable')
    ->where('column1', 3, '>=')
    ->closeOr()
    ->orWhere('column5', 'titi')
    ->findAll();
```

### Debugging

Debug similar to ADOdb debug:

```php
$db->debug();
```

### Instance

Get this instance anywhere in your code:

```php
AdamDb\Database::getInstance('myinstance')->table(...)
```
