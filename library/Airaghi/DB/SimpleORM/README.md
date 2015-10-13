
# Airaghi\DB\SimpleORM

A set of classes that gives you Database Abstraction, Generic Query Language and Orm features

**Very Short Documentation**

See the "test file"  https://github.com/davideairaghi/php/blob/master/test_simpleorm.php


**Short Documentation**

1. Enable autoloading

   first of all you need some "autoloading library", you can use your own or have a look at
   https://github.com/davideairaghi/php/blob/master/library/Airaghi/Tools/AutoLoad.php

2. Connect to the database
    
   You open a connection calling the static method *create($type,$params)* of the class
   *Airaghi\DB\SimpleORM\Adapter*, in $type you have to put the database type 
   (mysql is the only supported at the moment) and in $params you have to
   put all the parameters needed by the specific adapter (*AdapterMysql* in this case)
   When you do so you get back an object that represents the connection and that will be used by all the
   other SimpleORM's classes.
   
   ```
   $connection = \Airaghi\DB\SimpleORM\Adapter::create(
                        'mysql',
                        array(
                          'database'=>'test',
                          'username'=>'user',
                          'password'=>'pass',
                          'hostname'=>'localhost'
                        )
                  );
   ```

3. Define your models

   Each table in your database must have a "model" in your PHP code, this is done by creating a class with the 
   same name as the table but in lowercase and with only the first letter in uppercase.
   
   Each model-class you create have to extend *\Airaghi\DB\SimpleORM\Model* and have table columns as 
   public properties. See below an example.
   ```
   class Person extends \Airaghi\DB\SimpleORM\Model {
      public $id;
      public $email;
      public $name;
      public $date;
   }
   ```
   the system will automatically determine table name (the name of the class) and the primary key (*id*), but you 
   can override the default behaviour by giving a value to properties *$tableName* and *$primaryKey*. Regarding the
   primary key keep in mind that, at the moment, SimpleORM only allow keys made of a single column.

4. Create a "query" (to be used in select, update, delete, ...)

   SimpleORM let you create queries using an object oriented way, in fact everything is managed by the class  
   *\Airaghi\DB\SimpleORM\Query*.
   
   First of all you have to get an instance of a query and you have 2 ways to do it: 
   - create directy a new instance of \Airaghi\DB\SimpleORM\Query, doing so you get an "empty" query
     `$query = new \Airaghi\DB\SimpleORM\Query();`
   - call the method *initQuery()* of a model-class, doing so you get a query with the "from" part already compiled
     `$query = Person::initQuery()`
     
   Once you have obtained a new query object you can interact with it using the following methods:
   - setCount($column,$distinct) : 
     tell the query builder you want a "select count()" query, the column to *count* is in *$column* and you can also specify if you need a count(distinct ...) passing *true* as value for *$distinct*
   - setDistinct($distinct) : 
     tell the query builder you want a "select distinct" query, you can *reset* previous calls to thi method passing *false* as value for *$distinct*
   - setColumns($list) : 
     tell the query builder which columns you want to extract, each column name must be put as value for a specific item in *$list* array
   - setTables($list) : 
     tell the query builder which tables you want to use, at the moment fill the array with only one table (*\Airaghi\DB\SimpleORM\Model::initQuery()* already set the correct values and you don't need to call the method *setTables*)
   - setOffset($offset) : 
     tell the query builder which is the first record to retrieve, *$offset* is the index (start from 0)
   - setLimit($limit) : 
     tell the query builder how many records (*$limit*) to retrieve
   - setOrderBy($list) : 
     tell the query builder which ordering logic you want to use, every element of array *$list* can be a single value (a column name) or an array where index 0 contains the column name and index 1 contains the sorting direction (ASC or DESC)
   - setGroupBy($list) : 
     tell the query builder you want to group results according to the list of columns stored in the array *$list* (a column for each element)
   - setHaving() : 
     tell the query builder that *conditions* added from now on will be used inside the *having* clause
   - setWhere($condition) : 
     tell the query builder that *conditions* added from now on will be used inside the *where* clause; if you specify the string *$condition* the *where* clause will be set to its value
   - appendCondition($column,$operator,$value) : 
     tell the query builder to append to *where/having* a new condition; for each condition you have to specifcy column name in *$column*, what kind of operation to perform in *$operator* and the value for the *right side* of the operation in *$value*.
     operators allowed are: = , != , <> , < , <= , > , >= , IN , NOT IN , LIKE , NOT LIKE , IS NULL , IS NOT NULL
   - appendOpenBlock() : 
     tell the query builder to append to *where/having* an open block operand (an open parenthesis in standard sql)
   - appendCloseBlock() : 
     tell the query builder to append to *where/having* a close block operand (a closed parenthesis in standard sql)
   - appendAnd() : 
     tell the query builder to append to *where/having* a logical AND operator
   - appendOr() : 
     tell the query builder to append to *where/having* a logical OR operator
   - appendNot() : 
     tell the query builder to append to *where/having* a logical NOT operator
   - setCommand($cmd) : 
     tell the query builder which is the full command (*$cmd*) to execute; this will override conditions set using specific methods.
   - close()
     tell the query builder you have finished in giving commands
   - getCommandToExecute($cmdType,$extra) : 
     get the real query for the type of command (param *$cmdType*, values "insert","delete","select","update") to execute, for *insert* and *update* operations you have to pass in *$extra* the list of columns name (key *columns*) and the list of values (key *values*)

  Below you can find an example on how to *translate* the query *SELECT DISTINCT id FROM person WHERE id>0 AND (email<>'')* : 
  ```
  $query = Person::initQuery();
  $query->setDistinct()->setColumns(array('id'))->setWhere();
  $query->appendCondition('id','>','0')->appendAnd();
  $query->appendOpenBlock()->appendCondition('email','<>','""')->appendCloseBlock();
  $query->close();
  ```
  Remember that when you have to use in a query values generated *outside* (user input, ...) you can use the special 
  value *?* (if the value is a string do not enclose it in ' or ") and let the system do the real escape for you when
  you will give it the list of values to really use. See below for more information.
  
5. Extract records / Count records

  Every model has three static methods you can use to get data from the table it represents:
  - find($query,$params,$bindTypes,$adapter) : 
    extract records based on the given *$query*, replacing every *?* marker in the query with values in *$params*.
    If you want you can pass to the method also information about input data types (in *$bindTypes*) and
    a specific adapter to use (in *$adapter*).
  - findFirst($query,$params,$bindTypes,$adapter) : 
    extract the first record extracted from the database by the given *$query*, replacing every *?* marker in the
    query with values in *$params*.
    If you want you can pass to the method also information about input data types (in *$bindTypes*) and
    a specific adapter to use (in *$adapter*).
  - getCount($query,$params,$bindTypes,$adapter) : 
    count records extracted from the database by the given *$query*, replacing every *?* marker in the
    query with values in *$params*.
    If you want you can pass to the method also information about input data types (in *$bindTypes*) and
    a specific adapter to use (in *$adapter*).

  If you use *?* in your query remeber to specify in *$params* all the values in the same order.

  Examples:
  - find all the users with id > 3 and email not empty
  ```
  $query = Person::initQuery();
  $query->setWhere();
  $query->appendCondition('id','>','?')->appendAnd();
  $query->appendOpenBlock()->appendCondition('email','<>','""')->appendCloseBlock();
  $query->close();
  $list = Person::find($query,array(3));
  ```
  - find the first user inserted with email not empty
  ```
  $query = Person::initQuery();
  $query->setWhere();
  $query->appendCondition('email','<>','""');
  $query->setOrderBy('id');
  $query->close();
  $list = Person::find($query);
  ```
  - count users with email not empty
  ```
  $query = Person::initQuery();
  $query->setCount('id')->setWhere();
  $query->appendCondition('email','<>','?');
  $query->close();
  $list = Person::find($query,array(''));
  ```
6. Delete records

   Using a model you can delete records in two ways, delete records according to a query (static method call)
   and delete a record related to the model instance you are using (object method call):
  - deleteBatch($query,$params,$bindTypes,$adapter) : delete records matching the specified *$query* , 
    replacing every *?* marker in the query with values in *$params*.
    If you want you can pass to the method also information about input data types (in *$bindTypes*) and
    a specific adapter to use (in *$adapter*).
  - delete() : delete the record related to the object instance, using its primary key value a condition

  Examples:
  - delete all the users with empty email
  ```
    $query = Person::initQuery();
    $query->setWhere()->appendCondtion('email','=','?')->close();
    Person::deleteBatch($query,array(''));
  ```
  - delete the user with id=1
  ```
    $query = Person::initQuery();
    $query->setWhere()->appendCondtion('id','=',1)->close();
    $person = Person::findFirst($query);
    $person->delete();
  ```
7. Create a new record

   First of all you need to create a new instance of the model, than you simply have to fill the properties and than
   call the *save()* method. If the process ends without errors the save() method returns *true* and the primary key
   is filled with the correct value, otherwise the return value is *false* and the primary key is unfilled.
   
   Example
   ```
   $person = new Person();
   $person->name  = 'Your name';
   $person->email = 'test@test.com';
   if ($person->save()) {
     echo 'OK, primary key value is: '.$person->id.PHP_EOL;
   } else {
      echo 'ERROR'.PHP_EOL;
   }
   ```

8. Save changes to a record

   To change values inside a record you have to get a model instance, modify properties' values and the call the
   *save()* method. It's not so different from creating a new record.

   Example
   ```
   $query = Person::initQuery();
   $query->setWhere()->appendCondtion('id','=',1)->close();
   $person = Person::findFirst($query);
   $person->name  = 'Your new name';
   if ($person->save()) {
     echo 'OK, name changed'.PHP_EOL;
   } else {
      echo 'ERROR, name not changed'.PHP_EOL;
   }
   ```

999. Close the connection

     Simply call the method *close()* of the object obtained at the very first step, when you called
     *Airaghi\DB\SimpleORM\Adapter::create(...)*
     
     ```
     $connection->close();
     ```

**Long and complex Documentation**

See the code and the comments within it ;-)

**ChangeLog**

- 2015-10-13
  - added a short documentation in the README.md

- 2015-10-09
  - added support for "distinct" when creating "select" queries
  - added more examples (see test_simpleorm.php in the repository root directory)

- 2015-10-06
  - first release
