<?php

/* include autoload */
require_once(__DIR__ . '/library/Airaghi/Tools/AutoLoad.php' );
\Airaghi\Tools\AutoLoad::enableAutoLoad();

/* connect to mysql db */
$test = \Airaghi\DB\SimpleORM\Adapter::create('mysql',array('database'=>'test'));

/* model describing a table */
class Person extends \Airaghi\DB\SimpleORM\Model {
	public $id;
	public $email;
	public $nome;
	public $data;
}

/* create a query extracting only distinct records with email not empty and id equal to some value */
$query = Person::initQuery();
$query->setDistinct()->setWhere()->appendCondition('id','=','?')->appendAnd()->appendOpenBlock()->appendCondition('email','<>','""')->appendCloseBlock()->close();
$list = Person::find($query,array(1));
if ($list) {
	foreach ($list as $x) {
		print_r($x);
	}
}
$list->release();


/* create a query extracting only records with email not empty and id equal to some value */
$query = Person::initQuery();
$query->setWhere()->appendCondition('id','=','?')->appendAnd()->appendOpenBlock()->appendCondition('email','<>','""')->appendCloseBlock()->close();

/* count records matching the condition defined before and id=3 */
$tot = Person::getCount($query,array(3));

/* get the list or records matching the condition defined before and id=3 */
$list = Person::find($query,array(3));
if ($list) {
	foreach ($list as $l) {
		// print element
		print_r($l);
	}
}
$list->release();


/* get only the first record matching the condition defined before and id=3 */
$element = Person::findFirst($query,array(3));

/* delete every record with id>1 */
$query = Person::initQuery();
$query->setWhere()->appendCondition('id','>','?');
Person::deleteBatch($query,array(1),array(\Airaghi\DB\SimpleORM\Adapter::TYPE_INTEGER));

/* close db connection */
$test->close();



?>