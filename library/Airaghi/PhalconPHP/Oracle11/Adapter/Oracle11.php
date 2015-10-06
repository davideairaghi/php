<?php

namespace Airaghi\PhalconPHP\Oracle11\Adapter;

class Oracle11 extends  \Phalcon\Db\Adapter\Pdo\Oracle {


	public function __construct(array $descriptor) {
		if (!isset($descriptor['options'])) {
			$descriptor['options'] = array();
		}
		parent::__construct($descriptor);
		// echo '<pre>';print_r($this);echo '</pre>';die;
	}

	public function connect($descriptor = NULL) {
		static $connection = 0;
		++ $connection;
		if ($this->_connectionId > 0 && $descriptor === NULL) {
			return true;
		}
		if ($descriptor !== NULL) {
			$this->_descriptor = $descriptor;
		}
		$this->_pdo = new \yajra\Pdo\Oci8($this->_descriptor['dbname'],$this->_descriptor['username'],$this->_descriptor['password'],$this->_descriptor['options']);
		foreach ($this->_descriptor['options'] as $k=>$v) {
			$this->_pdo->setAttribute($k,$v);
		}
		$this->_connectionId = $connection;
		$this->_dialect = new \DB\Dialect\Oracle11;
		return true;
	}

	public function getConnectionId() {
		$str = trim(strval($this->_pdo->_dbh));
		$str = preg_replace('/^.*\#/','',$str);
		$this->_connectionId = $str;
		return $str;
	}

    public function escapeIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return "" . $identifier[0] . "." . $identifier[1] . "";
        }
        return "" . $identifier . "";
    }

	
	public function executePrepared(\PDOStatement $statement, array $placeholders, $dataTypes)  {
	
		$used_pp = false;
		$used_qm = false;
		$ph_counter = 0;
		
		$_placeholders = array();
		foreach ($placeholders as $pk=>$pv) {
			if ($pk === '?') {
				$pk = ':'.($ph_counter++);
				$used_qm = true;
			}
			if (is_int(($pk))) {
				$pk = ':'.$pk;
			}
			if (substr($pk,0,1)==':') {
				$used_pp = true;
				$_placeholders[intval(substr($pk,1))] = $pv;
			}
		}
		$placeholders = $_placeholders;
		
		if (!is_array($dataTypes)) {
			if ($dataTypes) { $dataTypes = array( $dataTypes ); }
			else            { $dataTypes = array(); }
		}
						
		$_datatypes   = array();
		foreach ($dataTypes as $pk=>$pv) {
			 $_pk = substr($pk,0,1);
			if (in_array($_pk,array('0','1','2','3','4','5','6','7','8','9'))) {
				 $_datatypes[$pk] = $pv;
			 }
		}
		$dataTypes = $_datatypes;

		if (count($placeholders) != count($dataTypes)) {
                    if (count($dataTypes)>count($placeholders)) {
                        array_splice($dataTypes, count($placeholders));
                    }
                    // fix PhalconPHP 2.0.4+ ...
                    // $index = count($placeholders)-1 ;
                    $last_index  = count($dataTypes);
                    $first_index = count($placeholders);
                    // echo $first_index.' '.$last_index.''.PHP_EOL;
                    if ($last_index <= 0) {
                        // dataTypes è vuoto ....
                        $first_index = 0;
                        $last_index  = count($placeholders);
                    }
                    for ($index=$first_index;$index<$last_index;$index++) {
                        // print_r($placeholders);die;
                        if (isset($placeholders[$index])) {
                                $val = $placeholders[ $index ];
                                $val = strtolower(gettype($val));
                                switch ($val) {
                                        case 'integer':
                                                $newval = \Phalcon\Db\Column::BIND_PARAM_INT;
                                                break;
                                        case 'float':
                                        case 'double':
                                                $newval = \Phalcon\Db\Column::BIND_PARAM_DECIMAL;
                                                break;
                                        case 'null':
                                                $newval = \Phalcon\Db\Column::BIND_PARAM_NULL;
                                                break;
                                        case 'string':
                                        default:
                                                $newval = \Phalcon\Db\Column::BIND_PARAM_STR;
                                                break;
                                }
                                $dataTypes[] = $newval;
                        } else {

                        }
                    }
                    
		}

		if ($used_pp) {
			$_placeholders = $placeholders;
			$_datatypes    = $dataTypes;
			$dataTypes     = array();
			$placeholders = array();
			foreach ($_placeholders as $pk=>$pv) {
				$placeholders[ str_replace('::',':',':'.$pk) ] = $pv;
			}
			foreach ($_datatypes as $pk=>$pv) {
				$dataTypes[ str_replace('::',':',':'.$pk) ] = $pv;
			}
		}
		
		if ($used_qm) {
			$_placeholders = $placeholders;
			$_datatypes    = $dataTypes;
			$dataTypes     = array();
			$placeholders = array();
			foreach ($_placeholders as $pk=>$pv) {
				$placeholders[ str_replace(':','',$pk) ] = $pv;
			}
			foreach ($_datatypes as $pk=>$pv) {
				$dataTypes[ str_replace(':','',$pk) ] = $pv;
			}
		}
                
		// echo 'STATEMENT: <pre>'; print_r($statement);echo '</pre><br>';
		// echo 'PLACEHOLDERS: <pre>';print_r($placeholders);echo '</pre><br>';
		// echo 'DATATYPES: <pre>';print_r($dataTypes);echo '</pre><br>';               

        if (!is_array($placeholders)) {
            throw new \Phalcon\Db\Exception("Placeholders must be an array");
        }
        
        foreach ($placeholders as $wildcard => $value) {
            $parameter = '';

            if (is_int($wildcard)) {
                $parameter = $wildcard + 1;
            } else {
                if (is_string($wildcard)) {
                    $parameter = $wildcard;
                } else {
                    throw new \Phalcon\Db\Exception("Invalid bind parameter (#1)");
                }
            }
                            
            if (is_array($dataTypes) && !empty($dataTypes)) {

                if (!isset($dataTypes[$wildcard])) {
                    throw new \Phalcon\Db\Exception("Invalid bind type parameter (#2)");
                }
                $type = $dataTypes[$wildcard];
                
                $castValue;
                if ($type == \Phalcon\Db\Column::BIND_PARAM_DECIMAL) {
                    $castValue = doubleval($value);
                    $type = \Phalcon\Db\Column::BIND_SKIP;
                } elseif ($value==='DEFAULT' ) {
                    $type      = \Phalcon\Db\Column::BIND_SKIP;
                    $castValue = null;
                } else {
                    $castValue = $value;
                }

                // echo $wildcard.' ['.$parameter.'] = '.$castValue.' ['.$type.']'.PHP_EOL;
                
                if ($type == \Phalcon\Db\Column::BIND_SKIP) {
					
                    $statement->bindParam($parameter, $castValue);
                    $statement->bindValue($parameter, $castValue);
                } else {
					
                    $statement->bindParam($parameter, $castValue, $type);
                    $statement->bindValue($parameter, $castValue, $type);
                }

            } else {
				
                $statement->bindParam($parameter, $value);
                $statement->bindValue($parameter, $value);
            }
        }
        
        $statement->execute();
        
        return $statement;
        
	}

}

?>