<?php

namespace Airaghi\DB\SimpleORM\ResultSets;

class ResultSetMysql extends \Airaghi\DB\SimpleORM\ResultSet {

	/*
	 * result to use
	 * @var \PDOStatement
	 */
	protected $result = null;

	/*
	 * create a new instance related to the result obtained by the Adapter
	 * @param \PDOStatement $result
	 */
	public function __construct(\PDOStatement &$result) {
		$this->result = &$result;
		$this->maxRecord = $this->result->rowCount() - 1;
		$this->currentRecord = 0;
	}
	
	/*
	 * release and empty the set
	 */
	public function release() {
		if ($this->result === null) {
			return;
		}
		parent::release();
		$this->result->closeCursor();
		unset($this->result);
		$this->result = null;
	}

	/*
	 * return the current record in the set
	 * @return \stdClass
	 */
	public function fetch() {
		if ($this->result === null || $this->currentRecord > $this->maxRecord) {
			return null;
		}
		$dbdata = $this->result->fetch(\PDO::FETCH_OBJ,\PDO::FETCH_ORI_ABS,$this->currentRecord);
		return $this->transformResult($dbdata);
	}	
	
}

?>