<?php

namespace Airaghi\DB\SimpleORM;

class ResultSet implements \Iterator {
	
	/*
	 * class to use when returing single result elements
	 * @var string
	 */
	protected $returnType = '\\stdClass';

	/*
	 * current record to fetch
	 * @var integer
	 */
	protected $currentRecord = 0;

	/*
	 * how much records we have
	 * @var integer
	 */	
	protected $maxRecord = -1;

	/*
	 * result to use
	 * @var mixed
	 */
	protected $result = null;
	
	/*
	 * create a new instance related to the result obtained by the Adapter
	 * @param mixed $result
	 */
	public function __construct(&$result) {
		$this->result = &$result;
		$this->currentRecord = 0;
		$this->maxRecord     = -1;
	}
	
	/*
	 * return the number of rows in the set
	 * @return integer
	 */
	public function numRows() {
			return 0;
	}

	
	/*
	 * set which class has to be used to create return single result elements
	 * @param string $type
	 */
	public function setReturnType($type) {
		$this->returnType = strval($type);
	}
	
	/*
	 * release and empty the set
	 */
	public function release() {
		$this->maxRecord = -1;
		$this->currentRecord = 0;
	}

	/*
	 * return the next record in the set and update the internal pointer
	 * @return \stdClass
	 */
	public function fetchNext() {
		$this->currentRecord ++;
		return $this->fetch();
	}
	
	/*
	 * return the current record in the set
	 * @return \stdClass
	 */
	public function fetch() {
		
	}
	
	/*
	 * set the next record to fetch
	 * @return \stdClass
	 */
	public function seek($n) {
		$n = intval($n);
		if ($n<0) {
			$n = 0;
		} elseif ($n>$this->maxRecord) {
			$n = $this->maxRecord;
		}
		$this->currentRecord = $n;
	}	


	/*
	 * return the current result 
	 * @return \stdClass
	 */
	public function current() {
		return $this->fetch();
	}

	/*
	 * set the next record position
	 */
	public function next() {
		$this->currentRecord ++;
	}
	
	/*
	 * return the current result position
	 * @return integer
	 */	
	public function key() {
		return $this->currentRecord;
	}

	/*
	 * reset the current result position
	 */		
	public function rewind() {
		$this->currentRecord = 0;
	}
	
	/*
	 * check if the current record pointer is valid
	 * @return boolean
	 */
	public function valid() {
		if ($this->currentRecord >= 0 && $this->currentRecord <= $this->maxRecord) {
			return true;
		} else {
			return false;
		}
	}
	
	/*
	 * trasform from a standard object to a specific object
	 * @return mixed
	 */
	protected function transformResult(&$result) {
		if (!$result) {
			return null;
		}
		if ($this->returnType == '\\stdClass' || $this->returnType == 'stdClass') {
			return $result;
		}
		$class = $this->returnType;
		$obj   = new $class();
		foreach ($result as $property=>$value) {
			if (property_exists($obj,$property)) {
				$obj->$property = $value;
			}
		}
		return $obj;
	}
	
}

?>