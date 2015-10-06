<?php

namespace Airaghi\DB\SimpleORM;

/*
 * Adapter  a class to handle db connections
 * @version 0.1
 * @notes   implements a very simple adapter (and relative dialect) to the database, very similar to what mysql/mariadb provides
 */
class Adapter {

	const TYPE_BOOLEAN = 'B';
	const TYPE_NULL    = '0';
	const TYPE_INTEGER = 'i';
	const TYPE_STRING  = 's';
	const TYPE_BINARY  = 'b';
	const TYPE_DECIMAL = 'd';
	const TYPE_DATE    = 'd';
	const TYPE_STMT    = 'X';
	const TYPE_GENERIC = '?';
	
	/*
	 * instance of the current adapter in use
	 * @var \Airaghi\DB\SimpleORM\Adapter
	*/
	protected static $adapter = null;
	
	/*
	 * connection resource
	 * @var \stdClass
	 */
	protected $connection = null;
	
	/*
	 * return the current adapter in use
	 * @return \Airaghi\DB\SimpleORM\Adapter
	 */
	public static function &getDefault() {
		return static::$adapter;
	}

	/*
	 * set the current adapter in use
	 * @param \Airaghi\DB\SimpleORM\Adapter $adapter
	 */	
	public static function setDefault(\Airaghi\DB\SimpleORM\Adapter &$adapter) {
		static::$adapter = &$adapter;
	}

	/*
	 * connect to the database service
	 * @param array  $options
	 */
	public function __construct($options=array()) {
		if (!\Airaghi\DB\SimpleORM\Adapter::getDefault()) {
			\Airaghi\DB\SimpleORM\Adapter::setDefault($this);
		}
	}
	
	/*
	 * return an instance of an adapter of a specific type
	 * @param string $type
	 * @param array  $options
	 * @return \Airaghi\DB\SimpleORM\Adapter
	 */
	public static function create($type,$options=array()) {
		$type  = ucwords(strtolower(strval($type)));
		$class = '\\Airaghi\\DB\\SimpleORM\\Adapters\\Adapter'.$type;
		return new $class($options);
	}
	
	/*
	 * close connection
	 */
	public function close() {
	}
	
	/*
	 * return the string representing the "all columns" instruction for the db
	 * @return string
	 */
	public function allColumns() {
		return '*';
	}
	
	/*
	 * escape a given identifier
	 * @param string $name
	 * @return string
	 */
	public function escapeIdentifier($name) {
		$name = strval($name);
		$name = str_replace( array(chr(0),"\n","\r","\t","'","\""), "", $name);
		return $name;
	}
	
	/*
	 * escape a given ordering direction
	 * @param string $name
	 * @return string
	 */
	protected function escapeOrderDirection($name) {
		$name = strval($name);
		$name = strtoupper($name);
		$ok   = array('','ASC','DESC');
		if (!in_array($name,$ok)) {
			$name = '';
		}
		return $name;
	}
	
	/*
	 * get a columns' list and escape each value
	 * @param array $cols
	 * @return array
	 */
	public function escapeColumns($cols) {
		$ret = array();
		if (!is_array($cols )) {
			$cols = array(strval($cols));
		}
		foreach ($cols as $col) {
			$ret[] = $this->escapeIdentifier($col);
		}
		return $ret;
	}

	/*
	 * escape a column name
	 * @param string $name
	 * @return string
	 */	
	public function escapeColumn($name) {
		$name = strval($name);
		return $this->escapeIdentifier($name);
	}
	
	/*
	 * escape a operator
	 * @param string $operator
	 * @return string
	 */	
	public function escapeOperator($operator) {
		$operator = strval($operator);
		$operator = strtoupper($operator);
		$ok = array( '=', '!=', '<>', '>', '<', '>=', '<=', 'IN',' NOT IN', 'LIKE', 'NOT LIKE' , 'IS NULL', 'IS NOT NULL' );
		if (!in_array($operator,$ok)) {
			return ' = ';
		}
		return ' '.$operator.' ';
	}
	
	/*
	 * escape e couple operator+value and return a string representation of the value to use in the query
	 * @param array $value
	 * @param string $operator
	 * @return string
	 */
	public function escapeValue($value,$operator) {
		$ret = '';
		$operator = $this->escapeOperator($operator);
		if ($operator == 'IS NULL' || $operator == 'IS NOT NULL') {
			return '';
		}
		if ($operator == 'IN' || $operator == 'NOT IN') {
			if (!is_array($value)) { 
				$value = array( $value );
			}
			$vals = array();
			foreach ($value as $v) {
				$vals[] = $this->escapeValue($v,'');
			}
			return $this->openBlock . implode(',',$vals) . $this->closeBlock;
		}
		if (is_array($value)) {
			$val   = array_shift($value);
			$value = $val;
		}
		$value = strval($value);
		return $value;
	}

	
	/*
	 * return the string representing the opening of a block
	 * @return string	 
	 */
	public function openBlock() {
		return '( ';
	}

	/*
	 * return the string representing the opening of a block
	 * @return string
	 */
	public function closeBlock() {
		return ' )';
	}	
	
	/*
	 * return the string representing the AND keyword
	 * @return string
	 */
	public function opAND() {
		return 'AND';
	}

	/*
	 * return the string representing the OR keyword
	 * @return string
	 */
	public function opOR() {
		return 'OR';
	}

	/*
	 * return the string representing the NOT keyword
	 * @return string
	 */
	public function opNOT() {
		return 'NOT';
	}

	/*
	 * return the string representing a count operation on the given column
	 * @param string  $column
	 * @param boolean $distinct
	 * @return string
	 */	 
	public function opCOUNT($column,$distinct) {
		if ($column === '') {
			$column = $this->allColumns();
		}
		if ($column !== $this->allColumns()) {
			$column   = $this->escapeIdentifier($column);
		}
		$distinct = (bool)$distinct;
		return 'COUNT('.($distinct?'DISTINCT ':'').$column.') as _numrows';
	}
	
	/*
	 * create a "select db command" based on the data received
	 * @param array $select
	 * @param array $from
	 * @param array $where
	 * @param array $groupby
	 * @param array $having
	 * @param array $orderby
	 * @param integer $offset
	 * @param integer $limit
	 * @return string
	 */
	public function parseSelect($select,$from,$where,$groupby,$having,$orderby,$offset,$limit) {
		if ($orderby) {
				foreach ($oderby as $k=>$v) {
					$orderby[$k] = $k.' '.$v;
				}
		}
		$query = 'SELECT '.implode(',',$select).' '.
				 'FROM '.implode(',',$from).' '.
				 'WHERE '.implode(' ',$where).' '.
				 ($groupby ? ' GROUP BY '.implode(',',$groupby).' ' : '').
				 ($groupby && $having ? 'HAVING '.implode(' ',$having).' ' : '').
				 ($orderby ? ' ORDER BY '.implode(',',$orderby).' ' : '').
				 ($limit>0 ? ' LIMIT '.$limit.' ' : '').
				 ($offset>0 ? ' OFFSET '.$offset.' ' : '').
				 '';
		return $query;
	}

	/*
	 * create a "insert db command" based on data received
	 * @param  string $table
	 * @param  array $columns
	 * @param  array $values
	 * @return string
	 */
	public function parseInsert($table,$columns=array(),$values=array()) {
		$query = 'INSERT INTO '.implode(',',$table).' '.
				  '('.implode(',',$columns).') VALUES '.
				  '('.implode(',',$values).')'.
				  '';
		return $query;
	}

	/*
	 * create a "update db command" based on data received
	 * @param  array $table
	 * @param  array $columns
	 * @param  array $values
	 * @param  array $where
	 * @return string
	 */	
	public function parseUpdate($table,$columns=array(),$values=array(),$where=array()) {
		$cmd = array();
		foreach ($columns as $k=>$col) {
			$cmd[] = $col.' = '.$values[$k];
		}
		if (!is_array($table)) {
			$table = array();
		}
		$query = 'UPDATE '.implode(',',$table).' '.
				 'SET '.implode(',',$cmd).' '.
				 ($where ? ' WHERE '.implode(' ',$where) : '').
				 '';
		return $query;
	}

	/*
	 * create a "delete db command" based on the data received
	 * @param array $from
	 * @param array $where
	 * @return string
	 */
	public function parseDelete($from,$where=array()) {
		$query = 'DELETE '.
				 'FROM '.implode(',',$from).' '.
				 ($where ? 'WHERE '.implode(' ',$where) : '').' '.
				 '';
		return $query;
	}
	
	
	/*
	 * execute a given command and return the results
	 * @param string $cmd
	 * @param array  $bindValues
	 * @param array  $bindTypes 
	 * @return \Airaghi\DB\SimpleORM\ResultSet
	 */
	public function select($cmd,$bindValues=array(),$bindTypes=array()) {
		return null;
	}
	
	/*
	 * execute a given command and return only execution status
	 * @param string $cmd
	 * @param array  $bindValues
	 * @param array  $bindTypes 
	 * @return boolean
	 */
	public function execute($cmd,$bindValues=array(),$bindTypes=array()) {
		return false;
	}	
	
	/*
	 * return the last id inserted into the db
	 * @return integer
	 */
	public function lastInsertId() {
		return 0;
	}
	
	/*
	 * begin a transaction
	 * @return boolean
	 */
	public function beginTransaction() {
		return true;
	}

	/*
	 * commit a transaction
	 * @return boolean
	 */	
	public function commitTransaction() {
		return true;
	}

	/*
	 * rollback a transaction
	 * @return boolean
	 */	
	public function rollbackTransaction() {
		return true;
	}

	/*
	 * return the last error message
	 * @return string 
	 */
	public function getLastErrorMsg() {
		return '';
	}
	
	/*
	 * return the last error code
	 * @return string
	 */
	public function getLastErrorCode() {
			return -1;
	}	
	
}

?>