<?php

namespace Airaghi\DB\SimpleORM\Adapters;

class AdapterMysql extends \Airaghi\DB\SimpleORM\Adapter {
	
	/*
	 * string representation of the backtick
	 * @var string
	 */
	protected $backtick = "`";
	
	/*
	 * connect to the database
	 * @param array  $options hostname,username,password,database,port,charset, ...
	 */
	public function __construct($options=array()) {
			$hostname = isset($options['hostname']) ? $options['hostname'] : 'localhost';
			$port     = isset($options['port']) ? $options['port'] : null;
			$username = isset($options['username']) ? $options['username'] : 'root';
			$password = isset($options['password']) ? $options['password'] : '';
			$database = isset($options['database']) ? $options['database'] : '';
			$charset  = isset($options['charset']) ? $options['charset'] : 'utf8';
			
			$dsn = 'mysql:'.
				   'host='.$hostname.';'.
				   ($port !== null ? 'port='.$port.';' : '').
				   'dbname='.$database.';'.
				   'charset='.$charset;
			unset($options['hostname']);
			unset($options['port']);
			unset($options['username']);
			unset($options['password']);
			unset($options['database']);
			unset($options['charset']);
			$this->connection = new \PDO($dsn, $username, $password, $options);
			if (!$this->connection) {
				$this->connection = null;
				throw new \Exception('DB: unable to connect');
			}
			$this->connection->exec('SET names '.$charset);
			if (!\Airaghi\DB\SimpleORM\Adapter::getDefault()) {
				\Airaghi\DB\SimpleORM\Adapter::setDefault($this);
			}
	}
	
	/*
	 * close connection
	 */
	public function close() {
		if ($this->connection) {
			unset($this->connection);
			$this->connection = null;
		}
	}

	/*
	 * return the "pdo version" of the data type  
	 * @return int
	 */
	protected function translateDataType($type) {
	
		switch ($type) {
				case \Airaghi\DB\SimpleORM\Adapter::TYPE_BOOLEAN:
					return \PDO::PARAM_BOOL;
					break;
				case \Airaghi\DB\SimpleORM\Adapter::TYPE_NULL:
					return \PDO::PARAM_NULL;
					break;
				case \Airaghi\DB\SimpleORM\Adapter::TYPE_INTEGER:
					return \PDO::PARAM_INT;
					break;
				case \Airaghi\DB\SimpleORM\Adapter::TYPE_STRING:
					return \PDO::PARAM_STR;
					break;
				case \Airaghi\DB\SimpleORM\Adapter::TYPE_BINARY:
					return \PDO::PARAM_LOB;
					break;
				case \Airaghi\DB\SimpleORM\Adapter::TYPE_DECIMAL:
					return \PDO::PARAM_STR;
					break;
				case \Airaghi\DB\SimpleORM\Adapter::TYPE_DATE:
					return \PDO::PARAM_STR;
					break;
				case \Airaghi\DB\SimpleORM\Adapter::TYPE_STMT:
					return \PDO::PARAM_STMT;
					break;
				case \Airaghi\DB\SimpleORM\Adapter::TYPE_GENERIC:
				default:
					return false;
					break;
		}
		return $ret;
	}

	/*
	 * prepare array for bindings
	 * @param array $bindValues
	 * @param array $bindTypes
	 */
	protected function parseBind(&$bindValues=array(),&$bindTypes=array()) {
		if ($bindValues) {
			$zero = false;
			foreach ($bindValues as $k=>$v) {
				if ($k === 0) {
					$zero = true;
				}
				if (!isset($bindTypes[$k])) {
					$bindTypes[$k] = \Airaghi\DB\SimpleORM\Adapter::TYPE_GENERIC;
				}
				if ($bindTypes[$k] == \Airaghi\DB\SimpleORM\Adapter::TYPE_GENERIC) {
					if (is_integer($v)) {
						$bindTypes[$k] = \Airaghi\DB\SimpleORM\Adapter::TYPE_INTEGER;
					} elseif (is_float($v)) {
						$bindTypes[$k] = \Airaghi\DB\SimpleORM\Adapter::TYPE_DECIMAL;
					} elseif (is_string($v)) {
						$bindTypes[$k] = \Airaghi\DB\SimpleORM\Adapter::TYPE_STRING;
					} else {
						$bindTypes[$k] = \Airaghi\DB\SimpleORM\Adapter::TYPE_GENERIC;
					}
				}
				$bindTypes[$k] = $this->translateDataType($bindTypes[$k]);
			}
			if ($zero) {
				$tmpV = array();
				$tmpK = array();
				foreach ($bindValues as $k=>$v) {
					if (is_int($k)) {
						$tmpV[$k+1] = $v;
						$tmpK[$k+1] = $bindTypes[$k];
					} else {
						$tmpV[$k] = $v;
						$tmpK[$k] = $bindTypes[$k];
					}
				}
				$bindValues = $tmpV;
				$bindTypes  = $tmpK;
			}
		} else {
			$bindTypes  = array();
			$bindValues = array();
		}
	}
	
	/*
	 * execute a given command and return the results
	 * @param string $cmd
	 * @param array  $bindValues
	 * @param array  $bindTypes 
	 * @return \Airaghi\DB\SimpleORM\ResultSet
	 */	
	public function select($cmd,$bindValues=array(),$bindTypes=array()) {
		if (!$this->connection) {
			return null;
		}
		$st = $this->connection->prepare($cmd,array(\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL));
		$this->parseBind($bindValues,$bindTypes);
		foreach ($bindValues as $key=>$val) {
			if ($bindTypes[$key]!==false) {
				$st->bindValue($key,$val,$bindTypes[$key]);
				$st->bindParam($key,$bindValues[$key],$bindTypes[$key]);
			} else {
				$st->bindValue($key,$val,$bindTypes[$key]);
				$st->bindParam($key,$bindValues[$key]);
			}
		}
		if (!$st->execute()) {
			return null;
		}
		return new \Airaghi\DB\SimpleORM\ResultSets\ResultSetMysql($st);
	}

	/*
	 * execute a given command and return only execution status
	 * @param string $cmd
	 * @param array  $bindValues
	 * @param array  $bindTypes 
	 * @return boolean
	 */
	public function execute($cmd,$bindValues=array(),$bindTypes=array()) {
		if (!$this->connection) {
			return false;
		}
		$st = $this->connection->prepare($cmd);
		$this->parseBind($bindValues,$bindTypes);
		foreach ($bindValues as $key=>$val) {
			if ($bindTypes[$key]!==false) {
				$st->bindValue($key,$val,$bindTypes[$key]);
				$st->bindParam($key,$bindValues[$key],$bindTypes[$key]);
			} else {
				$st->bindValue($key,$val,$bindTypes[$key]);
				$st->bindParam($key,$bindValues[$key]);
			}
		}
		return $st->execute();
	}	
	
	/*
	 * escape a given identifier
	 * @param string $name
	 * @return string
	 */
	public function escapeIdentifier($name) {
		if ($name == '*' || preg_match('#^([0-9]+)$#',$name) || preg_match('#^([0-9]+)\.([0-9]+)$#',$name)) {
			return $name;
		}
		$parts = explode('.',$name);
		foreach ($parts as $k=>$v) {
			$name = parent::escapeIdentifier($v);
			$name = str_replace($this->backtick,"",$name);
			$name = $this->backtick . $name . $this->backtick;
			$parts[$k] = $name;
		}
		$name = implode('.',$parts);
		return $name;
	}	
	
	/*
	 * escape a couple operator+value and return a string representation of the value to use in the query
	 * @param array $value
	 * @param string $operator
	 * @return string
	 */
	public function escapeValue($value,$operator) {
		$operator = $this->escapeOperator($operator);
		$value = parent::escapeValue($value,$operator);
		// check if we have an operator which requires the value to be sanitazed
		if (!in_array($operator,array('IN','NOT IN','IS NULL','IS NOT NULL'))) {
			// check if the value represent a possibile "placeholder"
			$placeholder = ($value == '?' || preg_match('#^([\?\:])([0-9a-zA-Z]+)$#',$value) || preg_match('#^([\:])([0-9a-zA-Z]+)([\:])$#',$value));
			if ($placeholder) {
				// we have a placeholder, do nothing
				return $value;
			}
			// we really have to sanitize the value 
			// but we do nothing more than a simple substitution ... 
			// we leave to the programmer to do the real job before calling us !
			$value = str_replace( array(chr(0)), "", $value);
		}
		return $value;
	}	

	/*
	 * return the last id inserted into the db
	 * @return integer
	 */
	public function lastInsertId() {
		if (!$this->connection) {
			return -1;
		}
		return $this->connection->lastInsertId();
	}

	/*
	 * begin a transaction
	 * @return boolean
	 */
	public function beginTransaction() {
		if (!$this->connection) {
			return false;
		}
		return $this->connection->beginTransaction();
	}

	/*
	 * commit a transaction
	 * @return boolean
	 */	
	public function commitTransaction() {
		if (!$this->connection) {
			return false;
		}
		return $this->connection->commit();
	}

	/*
	 * rollback a transaction
	 * @return boolean
	 */	
	public function rollbackTransaction() {
		if (!$this->connection) {
			return false;
		}
		return $this->connection->rollBack();
	}
	
	/*
	 * return the last error message
	 * @return string 
	 */
	public function getLastErrorMsg() {
		if (!$this->connection) {
			return '';
		}
		return implode('; ',$this->connection->errorInfo());
	}
	
	/*
	 * return the last error code
	 * @return string
	 */
	public function getLastErrorCode() {
		if (!$this->connection) {
			return -1;
		}
		return $this->connection->errorCode();
		
	}	
	
}

?>