<?php

namespace Airaghi\DB\SimpleORM;

/*
 * Query  a class to handle queries
 * @version   0.1
 * @notes     
 */
class Query {
	
	/*
	 * columns list
	 * @var array
	 */
	protected $columns = array();

	/*
	 * tables name
	 * @var array
	 */
	protected $tables = array();
	
	/*
	 * conditions list 
	 * @var array
	 */
	protected $where  = array();
	
	/*
	 * starting record
	 * @var integer
	 */
	protected $offset = 0;
	
	/*
	 * max number of records
	 * @var integer
	 */
	protected $limit  = 0;
	
	/*
	 * columns to use when grouping
	 * @var array
	 */
	protected $group  = array();

	/*
	 * having conditions list
	 * @var array
	 */
	protected $having = array();
	
	/*
	 * ordering condtions
	 * @var  array
	 */
	protected $order  = array();

	/*
	 * is the query closed ?
	 * @var boolean
	 */
	protected $closed = false;
	
	/*
	 * command to execute
	 * @var string
	 */
	protected $command = '';
	
	/*
	 * static instance cloned by method create()
	 * @var \Airaghi\DB\SimpleORM\Query
	 */	
	static protected $instance = null;

	/*
	 * instance of the db adapter
	 * @var \Airaghi\DB\SimpleORM\Adapter
	 */
	protected $adapter;
	
	/*
	 * current list of query (where|having)
	 * @var string
	 */
	private $current_conditions = '';
	
	/*
	 * prepare the new object and its parameters
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 */
	public function __construct(\Airaghi\DB\SimpleORM\Adapter &$adapter=null) {
		$this->reset();
		if ($adapter === null) {
			$adapter = \Airaghi\DB\SimpleORM\Adapter::getDefault();
		}
		$this->adapter = &$adapter;
		$this->setColumns( $this->adapter->allColumns());
	}
	
	/*
	 * reset every parameter
	 */
	protected function reset() {
		$this->columns = array();
		$this->tables   = array();
		$this->where   = array();
		$this->offset  = 0;
		$this->limit   = 0;
		$this->group   = array();
		$this->order   = array();
		$this->current_conditions = '';
		$this->closed   = false;
		$this->command  = '';
	}
	
	/*
	 * return a new instance of a query
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public static function create(\Airaghi\DB\SimpleORM\Adapter &$adapter=null) {
		if (static::$instance === null) {
			static::$instance = new \Airaghi\DB\SimpleORM\Query($adapter);
		}
		$ret = clone static::$instance;
		return $ret;
	}

	
	/*
	 * return the adapter 
	 * @return \Airaghi\DB\SimpleORM\Adapter
	 */
	public function &getAdapter() {
		return $this->adapter;
	}
	
	/*
	 * set the "count" operation
	 * @param string  $column
	 * @param boolean $distinct
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public function &setCount($column,$distinct=false) {
		$this->columns = array( $this->adapter->opCount($column,$distinct) );
		return $this;
	}
	
	/*
	 * set the specified columns to return
	 * @param array $list
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public function &setColumns($list=null) {
		if ($list === null) {
			$this->columns = array( $this->adapter->allColumns() );
		} else {
			$this->columns = $this->adapter->escapeColumns($list);
		}
		return $this;
	}
	
	/*
	 * set the tables to extract data from
	 * @param array $list
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public function &setTables($list) {
		if (!is_array($list)) {
			$list = array( strval($list) );
		}
		$tables = array();
		foreach ($list as $t) {
			$tables[] = $this->adapter->escapeIdentifier($t);
		}
		$this->tables = $tables;
		return $this;
	}
	
	/*
	 * set the first record to retrieve
	 * @param integer $offset
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public function &setOffset($offset) {
		$this->offset = intval($offset);
		return $this;
	}
	
	/*
	 * set the number of records to retrieve
	 * @param integer $offset
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public function &setLimit($limit) {
		$this->limit = intval($limit);
		return $this;
	}
	
	/*
	 * set the columns to use while ordering the results
	 * @param array $list
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public function &setOrderBy($list=null) {
		$orders = array();
		if ($list !== null) {
			if (!is_array($list)) {
				$list = array(strval($list));
			}
			foreach ($list as $ord) {
				if (!is_array($ord)) {
					$ord = array(strval($ord),'');
				}
				if (!isset($ord[1])) {
					$ord[1] = '';
				}
				$ord = array( $this->adapter->escapeIdentifier($ord[0]), $this->adapter->escapeOrderDirection($ord[1]) );
				$orders[] = $ord;
			}
			$this->order = $orders;
		}
		return $this;
	}	

	/*
	 * set the columns to use while grouping
	 * @param array $list
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public function &setGroupBy($list=null) {
		if ($list !== null) {
			$this->group = $this->adapter->escapeColumns($list);
		}
		return $this;
	}

	/*
	 * initialize the "having" conditions list
	 * @return \Airaghi\DB\SimpleORM\Query
	 */	
	public function &setHaving() {
		$this->having   = array();
		$this->current_conditions = 'having';
		return $this;
	}
	
	/*
	 * initialize the "where" conditions list
	 * @param string $condition
	 * @return \Airaghi\DB\SimpleORM\Query
	 */	
	public function &setWhere($condition='') {
		$this->where   = array();
		if ($condition !== '') {
			$this->where[] = $condition;
		}
		$this->current_conditions = 'where';
		return $this;
	}
	
	/*
	 * append to the list of conditions a specific one
	 * @param string $column
	 * @param string $operator
	 * @param array  $value
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public function &appendCondition($column,$operator,$value=null) {
		$operator = $this->adapter->escapeOperator($operator);
		$column   = $this->adapter->escapeColumn($column);
		$value    = $this->adapter->escapeValue($value,$operator);
		if ($this->current_conditions == 'where') {
			$this->where[] = $column . $operator . $value;
		}
		if ($this->current_conditions == 'having') {
			$this->having[] = $column . $operator . $value;
		}
		return $this;
	}

	/*
	 * append to the list an open block command
	 * @return \Airaghi\DB\SimpleORM\Query
	 */	
	public function &appendOpenBlock() {
		if ($this->current_conditions == 'where') {
			$this->where[] = $this->adapter->openBlock();
		}
		if ($this->current_conditions == 'having') {
			$this->having[] = $this->adapter->openBlock();
		}
		return $this;
	}

	/*
	 * append to the list an close block command
	 * @return \Airaghi\DB\SimpleORM\Query
	 */	
	public function &appendCloseBlock() {
		if ($this->current_conditions == 'where') {
			$this->where[] = $this->adapter->closeBlock();
		}
		if ($this->current_conditions == 'having') {
			$this->having[] = $this->adapter->closeBlock();
		}
		return $this;
	}

	/*
	 * append to the list a "AND" operator
	 * @return \Airaghi\DB\SimpleORM\Query 
	 */
	public function &appendAnd() {
		if ($this->current_conditions == 'where') {
			$this->where[] = $this->adapter->opAND();
		}
		if ($this->current_conditions == 'having') {
			$this->having[] = $this->adapter->opAND();
		}
		return $this;		
	}
	
	/*
	 * append to the list a "OR" operator
	 * @return \Airaghi\DB\SimpleORM\Query 
	 */
	public function &appendOr() {
		if ($this->current_conditions == 'where') {
			$this->where[] = $this->adapter->opOR();
		}
		if ($this->current_conditions == 'having') {
			$this->having[] = $this->adapter->opOR();
		}
		return $this;		
	}	

	/*
	 * append to the list a "NOT" operator
	 * @return \Airaghi\DB\SimpleORM\Query 
	 */
	public function &appendNot() {
		if ($this->current_conditions == 'where') {
			$this->where[] = $this->adapter->opNOT();
		}
		if ($this->current_conditions == 'having') {
			$this->having[] = $this->adapter->opNOT();
		}
		return $this;		
	}
	
	/*
	 * close a query and prepare 
	 */
	public function close() {
		$this->closed = true;
	}
	
	/*
	 * set a "raw command" ... skipping the building mechanism
	 * @param string $cmd
	 * @return \Airaghi\DB\SimpleORM\Query
	 */
	public function &setCommand($cmd) {
		$cmd = strval($cmd);
		$this->command = $cmd;
		$this->close();
		return $this;
	}
	
	/*
	 * return the command to send to the database
	 * @param string $cmdType
	 * @param array  $extra
	 * @return string
	 */
	public function getCommandToExecute($cmdType='select',$extra=array()) {
		if (!$this->closed) {
			return null;
		}
		switch ($cmdType) {
			case 'insert':
				$this->command = $this->adapter->parseInsert($this->tables, $extra['columns'], $extra['values']);
				break;
			case 'update':
				$this->command = $this->adapter->parseUpdate($this->tables, $extra['columns'], $extra['values'], $this->where);
				break;
			case 'delete':
				$this->command = $this->adapter->parseDelete($this->tables, $this->where);
				break;
			case 'select':
			default:
				$this->command = $this->adapter->parseSelect($this->columns, $this->tables, $this->where, $this->group, $this->having, $this->order, $this->offset, $this->limit);
				break;
		}
		return $this->command;
	}
	
}

?>