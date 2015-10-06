<?php

namespace Airaghi\DB\SimpleORM;

/*
 * Model    a class to handle data mapped to database tables
 * @version 0.1
 * @notes   only tables with exactly one primary key are supported and it has to be an integer number
 *          table names automatically determined will be forced to lower case
 */
class Model {
	
	/*
	 * table name, if empty it will be auto determined by the constructor
	 * @var string
	 */
	protected $tableName = '';
	
	/*
	 * primary key column name, if empty it will be set to "id"
	 * @var string
	 */
	protected $primaryKey = '';
	
	/*
	 * static instance cloned by method create()
	 * @var \Airaghi\DB\SimpleORM\Model
	 */
	static protected $instance = null;
	
	/*
	 * instance of the db adapter
	 * @var \Airaghi\DB\SimpleORM\Adapter
	 */
	protected $adapter;
	
	/*
	 * get table name
	 * @return string
	 */
	protected static function getTableName() {
		$obj = static::create();
		$name  = $obj->buildTableName();
		unset($obj);
		return $name;
	}
	
	/*
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 * cerate a new object based on the model
	 */
	public function __construct(\Airaghi\DB\SimpleORM\Adapter &$adapter=null) {
		if ($this->primaryKey === '') {
			$this->primaryKey = 'id';
		}
		if ($this->tableName === '') {
			$this->tableName = $this->buildTableName();
		}
		if ($adapter === null) {
			$adapter = \Airaghi\DB\SimpleORM\Adapter::getDefault();
		}
		$this->adapter = &$adapter;
	}
	
	/*
	 * return a new instance of the model
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 * @return \Airaghi\DB\SimpleORM\Model
	 */
	static public function create(\Airaghi\DB\SimpleORM\Adapter &$adapter=null) {
		if (static::$instance === null) {
			$class = get_called_class();
			static::$instance = new $class($adapter);
		}
		$ret = clone static::$instance;
		return $ret;
	}
	
	/*
	 * get the field value (only for public properties
	 * @return mixed
	 */
	public function getField($name) {
		$prop = new \ReflectionProperty(get_class($this),$name);
		if (!$prop->isPublic()) {
			return null;
		}
		unset($prop);
		return $this->$name;
	}
	
	/*
	 * build the table name
	 * @return string
	 */ 
	public function buildTableName() {
		$class = get_class($this);
		$parts = explode('\\',$class);
		$parts = array_pop($parts);
		$parts = preg_split('/(?=[A-Z])/',$parts);
		$tmp   = array();
		foreach ($parts as $p) {
			$p = trim($p);
			if ($p!='') {
				$tmp[] = $p;
			}
		}
		return strtolower(implode('_',$tmp));
	}
	
	/*
	 * return a Query instance
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	static public function initQuery(\Airaghi\DB\SimpleORM\Adapter &$adapter=null) {
		if ($adapter === null) {
			$adapter = \Airaghi\DB\SimpleORM\Adapter::getDefault();
		}
		$class = get_called_class();
		$name  = $class::getTableName();
		$qry   = new \Airaghi\DB\SimpleORM\Query($adapter);
		$qry->setTables($name);
		return $qry;
	}
	
	/*
	 * return the adapter used by the Model
	 * @return \Airaghi\DB\SimpleORM\Adapter
	 */
	public function &getAdapter() {
		return $this->adapter;
	}
	
	/*
	 * save the record and set the primary key
	 * @return boolean
	 */
	public function save() {
		$pk = $this->primaryKey;
		if ($pk == '') {
			// if no primary key force an insert !
			return $this->insert();
		}
		if ($pk!='' && !$this->$pk) {
			// if no primary key value force an insert !
			return $this->insert();
		}
		$cols = $vals = $markers = array();
		foreach ($this as $p_name=>$p_val) {
			$prop = new \ReflectionProperty(get_class($this),$p_name);
			if ($prop->isPublic() && $p_name != $this->primaryKey) {
				$cols[]    = $this->adapter->escapeIdentifier($p_name);
				$markers[] = '?';
				$vals[]    = $p_val;
			}
		}
		$query = new \Airaghi\DB\SimpleORM\Query($this->adapter);
		$query->setTables( $this->tableName );
		$query->setWhere();
		$query->appendCondition($pk,'=','?');
		$query->close();
		$cmd = $query->getCommandToExecute('update',array('columns'=>$cols,'values'=>$markers));
		$adapterToCall = $query->getAdapter();
		$vals[] = $this->$pk;
		$ok = $adapterToCall->execute($cmd,$vals);
		return $ok;
	}

	/*
	 * save the record and set the primary key
	 * the operation is forced to be a INSERT (even if a primary key is present)
	 * @return boolean
	 */
	public function insert() {
		$cols = $vals = $markers = array();
		foreach ($this as $p_name=>$p_val) {
			$prop = new \ReflectionProperty(get_class($this),$p_name);
			if ($prop->isPublic() && $p_name != $this->primaryKey) {
				$cols[]    = $this->adapter->escapeIdentifier($p_name);
				$markers[] = '?';
				$vals[]    = $p_val;
			}
		}
		$query = new \Airaghi\DB\SimpleORM\Query($this->adapter);
		$query->setTables( $this->tableName );
		$query->close();
		$cmd = $query->getCommandToExecute('insert',array('columns'=>$cols,'values'=>$markers));
		$adapterToCall = $query->getAdapter();
		$ok = $adapterToCall->execute($cmd,$vals);
		if ($ok) {
			$pk = $this->primaryKey;
			$this->$pk = $this->adapter->lastInsertId();
		}
		return $ok;
	}


	/*
	 * delete the record
	 * @return boolean
	 */
	public function delete() {
		$pk = $this->primaryKey;
		if ($pk == '') {
			// at the moment can't delete records without primary key
			return false;
		}
		$query = new \Airaghi\DB\SimpleORM\Query($this->adapter);
		$query->setTables( $this->tableName );
		$query->setWhere();
		$query->appendCondition($pk,'=','?');
		$query->close();
		$cmd = $query->getCommandToExecute('delete');
		$adapterToCall = $query->getAdapter();
		$bindValues = array($this->$pk);
		$ok = $adapterToCall->execute($cmd,$bindValues);
		if ($ok) {
			$this->$pk = '';
		}
		return $ok;
	}
	
	/*
	 * delete records based on a option condition
	 * @param \Airaghi\DB\SimpleORM\Query $query 
	 * @param array $bindValues
	 * @param array $bindTypes
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 * @return boolean
	 */
	static public function deleteBatch(\Airaghi\DB\SimpleORM\Query $query = null,$bindValues=array(),$bindTypes=array(),\Airaghi\DB\SimpleORM\Adapter &$adapter=null) {
		if ($query === null) {
			$query = static::initQuery($adapter);
		}
		$query->close();
		$cmd = $query->getCommandToExecute('delete');
		$adapterToCall = $query->getAdapter();
		$ok = $adapterToCall->execute($cmd,$bindValues,$bindTypes);
		return $ok;
	}
	
	/*
	 * select records based on a optional codition
	 * @param \Airaghi\DB\SimpleORM\Query $query 
	 * @param array $bindValues
	 * @param array $bindTypes	 
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 * @return \Airaghi\DB\SimpleORM\ResultSet
	 */
	static public function find(\Airaghi\DB\SimpleORM\Query $query=null,$bindValues=array(),$bindTypes=array(),\Airaghi\DB\SimpleORM\Adapter &$adapter=null) {
		if ($query === null) {
			$query = static::initQuery($adapter);
		}
		$query->close();
		$cmd = $query->getCommandToExecute('select');
		$adapterToCall = $query->getAdapter();
		$result = $adapterToCall->select($cmd,$bindValues,$bindTypes);
		if (!$result) {
			return null;
		}
		$result->setReturnType(get_called_class());
		return $result;
	}

	/*
	 * count records based on a optional codition
	 * @param \Airaghi\DB\SimpleORM\Query $query 
	 * @param array $bindValues
	 * @param array $bindTypes	 
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 * @return integer
	 */
	static public function getCount(\Airaghi\DB\SimpleORM\Query $query = null,$bindValues=array(),$bindTypes=array(),\Airaghi\DB\SimpleORM\Adapter &$adapter=null) {
		if ($query === null) {
			$query = $query = static::initQuery($adapter);
		} else {
			$query = clone $query;
		}
		$query->setCount('');
		$query->close();
		$cmd = $query->getCommandToExecute('select');
		$adapterToCall = $query->getAdapter();
		$result = $adapterToCall->select($cmd,$bindValues,$bindTypes);
		if (!$result) {
			return 0;
		}
		$data = $result->fetch();
		$result->release();
		unset($result);
		if (!$data) {
			return 0;
		}
		return $data->_numrows;
	}	
	
	/*
	 * select the first record of a selection based on a optional codition
	 * @param \Airaghi\DB\SimpleORM\Query $query 
	 * @param array $bindValues
	 * @param array $bindTypes	 
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 * @return \Airaghi\DB\SimpleORM\Model
	 */
	static public function findFirst(\Airaghi\DB\SimpleORM\Query $query=null,$bindValues=array(),$bindTypes=array(),\Airaghi\DB\SimpleORM\Adapter &$adapter=null) {
		if ($query === null) {
			$query = $query = static::initQuery($adapter);
		}
		$query->setOffset(0);
		$query->setLimit(1);
		$list = static::find($query,$bindValues,$bindTypes,$adapter);
		if (!$list) {
			return null;
		}
		$ret  = null;
		foreach ($list as $x) {
			$ret = $x;
			break;
		}
		return $ret;
	}
	

	
}

?>