<?php

// original work: https://github.com/fishjerky/phalcon-mssql
// modified by Davide Airaghi (www.airaghi.net) to use with SQL Server 2008 and Microsoft SQL Server PDO driver
// version: 0.1.1
// PhalconPHP: starting from version 3.0.x
// Note: rename this file to Mssql.php before using it

namespace Airaghi\PhalconPHP\MSQQL\Adapter;

use Phalcon;
use Phalcon\Db\Column;
use Phalcon\Db\Adapter\Pdo as AdapterPdo;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Db\AdapterInterface;

class Mssql extends AdapterPdo implements EventsAwareInterface, AdapterInterface
{

	protected $instance;

	protected $_lastID = false;
    protected $_type = 'mssql';
    //	protected $_dialectType = 'sqlsrv';

    // public function __construct($descriptor) // 1.x
    public function __construct(array $descriptor) // 2.x
    {
        $this->connect($descriptor);
		$this->instance = microtime();
    }

    /**
     * Escapes a column/table/schema name
     *
     *<code>
     *	$escapedTable = $connection->escapeIdentifier('robots');
     *	$escapedTable = $connection->escapeIdentifier(array('store', 'robots'));
     *</code>
     *
     * @param string identifier
     * @return string
     */
    public function escapeIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return "[" . $identifier[0] . "].[" . $identifier[1] . "]";
        }
        return "[" . $identifier . "]";
    }


    public function describeColumns($table, $schema = null)
    {
        $describe;
        $columns;
        $columnType;
        $field;
        $definition;
        $oldColumn;
        $dialect;
        $sizePattern;
        $matches;
        $matchOne;
        $columnName;

        /**
         * Get the SQL to describe a table
         * We're using FETCH_NUM to fetch the columns
         * Get the describe
         */

        //1. get pk
        $primaryKeys = array();
        $describeKeys = $this->fetchAll("exec sp_pkeys @table_name = '$table'");
        foreach ($describeKeys as $field) {
            $primaryKeys[$field['COLUMN_NAME']] = true;
        }
		
        //2.get column description
        $dialect = $this->_dialect;
        $describe = $this->fetchAll($dialect->describeColumns($table, $schema), \Phalcon\Db::FETCH_ASSOC);

        $oldColumn = null;
        $sizePattern = "#\\(([0-9]+)(,[0-9]+)*\\)#";

        $columns = array();

        /**
         * Field Indexes: 0:name, 1:type, 2:not null, 3:key, 4:default, 5:extra
         */
        foreach ($describe as $field) {

            /**
             * By default the bind types is two
             */
            $definition = array(
                    "bindType"	=> 2,
                    "unsigned"	=> false,
                    );

            /**
             * By checking every column type we convert it to a Phalcon\Db\Column
             */
            $columnType = $field['TYPE_NAME'];

            $autoIncrement = false;
            switch ($field['TYPE_NAME']) {
                case 'int identity':
                    $definition['type'] = Column::TYPE_INTEGER;
                    $definition["isNumeric"] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    $autoIncrement = true;
                    break;
                case 'int':
                    $definition['type'] = Column::TYPE_INTEGER;
                    $definition["isNumeric"] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    break;
                case 'nchar':
                    $definition['type'] = Column::TYPE_VARCHAR;
                    break;
                case 'char':
                    $definition['type'] = Column::TYPE_CHAR;
                    break;
                case 'smallint':
                    $definition['type'] = Column::TYPE_INTEGER;
                    $definition["isNumeric"] = true;
                    $definition['bindType'] = Column::BIND_PARAM_INT;
                    break;
                case 'float':
                    $definition['type'] = Column::TYPE_DECIMAL;
                    $definition["isNumeric"] = true;
                    $definition['bindType'] = Column::BIND_SKIP;
                    break;
                case 'datetime':
                    $definition["type"] = Column::TYPE_DATETIME;
                    break;
                case 'date':
                    $definition["type"] = Column::TYPE_DATE;
                    break;
                case 'decimal':
                    $definition["type"] = Column::TYPE_DECIMAL;
                    $definition["isNumeric"] = true;
                    $definition["bindType"] = Column::BIND_PARAM_DECIMAL;
                    break;
                case 'text':
                    $definition["type"] = Column::TYPE_TEXT;
                    break;
                case 'numeric':
                    $definition["type"] = Column::TYPE_FLOAT;
                    $definition["isNumeric"] = true;
                    $definition["bindType"] = Column::TYPE_DECIMAL;
                    break;
                default:
                    //echo $field['COLUMN_NAME'] . 'has no match type: ' .  $field['TYPE_NAME'] . PHP_EOL;
                    $definition['type'] = Column::TYPE_VARCHAR;
                    //$definition['bindType'] = Column::BIND_PARAM_STR;
            }

            /**
             * If the column type has a parentheses we try to get the column size from it
             */
            $definition["size"] = (int)$field['LENGTH'];
            $definition["precision"] = (int)$field['PRECISION'];

            /**
             * Positions
             */
            if (!$oldColumn) {
                $definition["first"] = true;
            } else {
                $definition["after"] = $oldColumn;
            }

            /**
             * Check if the field is primary key
             */
            if (isset($primaryKeys[$field['COLUMN_NAME']])) {
                $definition["primary"] = true;
            }

            /**
             * Check if the column allows null values
             */
            $definition["notNull"] = ($field['NULLABLE'] == 0);

            /*
             */
            if ($field['SCALE'] || $field['SCALE'] == '0') {
                //$definition["scale"] = (int)$field['SCALE'];  Phalcon/Db/Column type does not support scale parameter
                $definition["size"] = $definition['precision'];
            }
            /**
             * Check if the column is auto increment
             */
            if ($autoIncrement) {
                $definition["autoIncrement"] = true;
            }

            /**
             * Every route is stored as a Phalcon\Db\Column
             */
            $columnName = $field['COLUMN_NAME'];
            //echo $columnName  . PHP_EOL;

            $columns[] = new \Phalcon\Db\Column($columnName, $definition);
            $oldColumn = $columnName;
        }
		
		//echo '<pre>';print_r($columns);echo '</pre>';
        return $columns;
    }

    public function connect(array $descriptor = null)
    {	
		if (strpos($descriptor['host'],';')!==false) {
			$tmp = explode(';',$descriptor['host']);
			$descriptor['failover'] = $tmp[1];
			$descriptor['host'] = $tmp[0];
		} else {
			$descriptor['failover'] = $descriptor['host'];
		}		
        $this->_pdo = new \PDO(
            $descriptor['pdoType'].':'.
			'server='.$descriptor['host'].';'.
			'database='.$descriptor['dbname'].';'.
			'MultipleActiveResultSets=1;'.
			'Failover_Partner='.$descriptor['failover'],
            $descriptor['username'],
            $descriptor['password']
        );
		$this->_pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
		$this->_pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES,true);
		// $this->_pdo->setAttribute(\PDO::SQLSRV_ATTR_DIRECT_QUERY,true);
		
        $this->execute('SET QUOTED_IDENTIFIER ON');

        $this->_dialect = new \Airaghi\PhalconPHP\MSQQL\Dialect\Mssql();
    }

    public function query($sql, $bindParams = null, $bindTypes = null)
    {

		// echo '---- ---- ---- ---- ----<br><br>';
        if (is_string($sql)) {
            //check sql server keyword
            if (!strpos($sql, '[rowcount]')) {
                $sql = str_replace('rowcount', '[rowcount]', $sql);	//sql server keywords
            }

            //case 1. select count(query builder)
            $countString = 'SELECT COUNT(*)';
            if (strpos($sql, $countString)) {
                  $sql = str_replace('"', '', $sql);
                  return parent::query($sql, $bindParams, $bindTypes);
            }


            //case 2. subquery need alais name (model find)
            $countString = 'SELECT COUNT(*) "numrows" ';
            if (strpos($sql, $countString) !== false) {
                $sql .= ' dt ';
				// $sql = preg_replace('/ORDER\sBY.*\)\ dt/i',') dt',$sql);
                
				//subquery need TOP
                if (strpos($sql, 'TOP') === false) {
                    if (strpos($sql, 'ORDER') !== false) {
                        $offset = count($countString);
                        $pos = strpos($sql, 'SELECT', $offset) + 7; //'SELECT ';
						if (stripos($sql,'SELECT DISTINCT') === false) {
							$sql = substr($sql, 0, $pos) .  'TOP 100 PERCENT '. substr($sql, $pos);
						}
                    }
                }
            }
			
			
            // echo $sql."<br><br>";
			
            //sql server(dblib) does not accept " as escaper
            $sql = str_replace('"', '', $sql);
        }
		
		// echo $sql.'<br><br>------ --------- ----------';
        return parent::query($sql, $bindParams, $bindTypes);

    }

    /**
     * Appends a LIMIT clause to $sqlQuery argument
     *
     * <code>
     *	echo $connection->limit("SELECT * FROM robots", 5);
     * </code>
     *
     * @param	string sqlQuery
     * @param	int number
     * @return	string
     */
    public function limit($sqlQuery, $number)
    {
        $dialect = $this->_dialect;
        return $dialect->limit($sqlQuery, $number);
    }


    //insert miss parameters, need to do this
	// public function executePrepared($statement, $placeholders, $dataTypes)  // 1.x
	public function executePrepared(\PDOStatement $statement, array $placeholders, $dataTypes)  // 2.x
    {

		/*
		$sql = ($statement->queryString); 
		if (substr($sql,0,6)=='UPDATE' || substr($sql,0,6)=='INSERT') {
			echo $sql."<br>";
			print_r($placeholders);
			die;
		}
		*/
            
            
                // fix PhalconPHP 2.0.4+ ... 
            
                $used_pp = false;
                $used_qm = false;
                $ph_counter = 0;
                
                $_placeholders = array();
                foreach ($placeholders as $pk=>$pv) {
                    if ($pk == '?') {
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
                
                // fine fix PhalconPHP 2.0.4+ ... 
                
                // $placeholders = array( ':0' => '/' ); $dataTypes    = array( ':0' => \Phalcon\Db\Column::BIND_PARAM_STR );
                    
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
                        if (isset($placeholders[$index]) || array_key_exists($index,$placeholders)) {
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
                    // fine fix PhalconPHP 2.0.4+ ...
		}
	
                
                // fix PhalconPHP 2.0.4+ ... 
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
                
                // fine fix PhalconPHP 2.0.4+ ... 
                
		if (defined('BLOCKSQL')) {
			echo 'STATEMENT: '; print_r($statement);echo '<br>';
			echo 'PLACEHOLDERS: ';print_r($placeholders);echo '<br>';
			echo 'DATATYPES: ';print_r($dataTypes);echo '<br>';
			die;
		}
        //return $this->_pdo->prepare($statement->queryString, $placeholders);//not working

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

                /**
                 * The bind type is double so we try to get the double value
                 */
                $castValue;
                if ($type == \Phalcon\Db\Column::BIND_PARAM_DECIMAL) {
                    $castValue = doubleval($value);
                    $type = \Phalcon\Db\Column::BIND_SKIP;
                // fix PhalconPHP 2.0.4+ ...
                } elseif ($value==='DEFAULT' ) {
                    $type      = \Phalcon\Db\Column::BIND_SKIP;
                    $castValue = null;
                // fine fix PhalconPHP 2.0.4+ ...
                } else {
                    $castValue = $value;
                }

                /**
                 * 1024 is ignore the bind type
                 */
                if ($type == \Phalcon\Db\Column::BIND_SKIP) {
                    $statement->bindParam($parameter, $castValue);
                    $statement->bindValue($parameter, $castValue);
                } else {
                    $statement->bindParam($parameter, $castValue, $type);
                    $statement->bindParam($parameter, $castValue, $type);
                    $statement->bindValue($parameter, $castValue, $type);
                }

            } else {
                $statement->bindParam($parameter, $value);		//TODO: works for model, but not pdo - all column with the latest parameter value
                $statement->bindValue($parameter, $value);	//works for pdo , but not model
            }
        }

        //echo PHP_EOL . $statement->queryString . PHP_EOL;
		// echo '<br><br>';print_r($statement).'<br><br>';
        $statement->execute();
        return $statement;
    }	

    // public function insert($table, $values, $fields = null, $dataTypes = null) // 1.x
	public function insert($table, array $values, $fields = NULL, $dataTypes = NULL) // 2.x
    {
        $placeholders;
        $insertValues;
        $bindDataTypes;
        $bindType;
        $position;
        $value;
        $escapedTable;
        $joinedValues;
        $escapedFields;
        $field;
        $insertSql;

        if (!is_array($values)) {
            throw new \Phalcon\Db\Exception("The second parameter for insert isn't an Array");
        }

        /**
         * A valid array with more than one element is required
         */
        if (!count($values)) {
            throw new \Phalcon\Db\Exception("Unable to insert into " . $table . " without data");
        }

        $placeholders = array();
        $insertValues = array();

        if (!is_array($dataTypes)) {
            $bindDataTypes = array();
        } else {
            $bindDataTypes = $dataTypes;
        }

        /**
         * Objects are casted using __toString, null values are converted to string "null", everything else is passed as "?"
         */
        //echo PHP_EOL;	var_dump($dataTypes);
        foreach ($values as $position => $value) {
            if (is_object($value)) {
                $placeholders[] = '?'; // (string) $value;
				$insertValues[] = (string) $value;
            } else {
                if ($value === null) { // (0 ==) null is true
                    $placeholders[] = '?';  // "default";
					$insertValues[] = null; // "default";
                } else {
                    $placeholders[] = "?";
                    $insertValues[] = $value;
                    if (is_array($dataTypes)) {
                        if (!isset($dataTypes[$position])) {
                            throw new \Phalcon\Db\Exception("Incomplete number of bind types");
                        }
                        $bindType = $dataTypes[$position];
                        $bindDataTypes[] = $bindType;
                    }
                }
            }
        }
        // if (defined('DEBUG')) { var_dump($placeholders); die; }

        if (false) { //globals_get("db.escape_identifiers") {
            $escapedTable = $this->escapeIdentifier($table);
        } else {
            $escapedTable = $table;
        }

        /**
         * Build the final SQL INSERT statement
         */
        $joinedValues = join(", ", $placeholders);
        if (is_array($fields)) {

            if (false) {//globals_get("db.escape_identifiers") {
                $escapedFields = array();
                foreach ($fields as $field) {
                    $escapedFields[] = $this->escapeIdentifier($field);
                }
            } else {
                $escapedFields = $fields;
            }

            $insertSql = "INSERT INTO " . $escapedTable . " (" . join(", ", $escapedFields) . ") VALUES (" . $joinedValues . ")";
        } else {
            $insertSql = "INSERT INTO " . $escapedTable . " VALUES (" . $joinedValues . ")";
        }

		$insertSql = 'SET NOCOUNT ON; '.$insertSql.'; SELECT CAST(SCOPE_IDENTITY() as int) as newid';
		
		
        /**
         * Perform the execution via PDO::execute
         */
		$obj = $this->query($insertSql, $insertValues, $bindDataTypes);
		$ret = $obj->fetchAll();
		if ($ret && isset($ret[0]) && isset($ret[0]['newid'])) {
			$this->_lastID = $ret[0]['newid'];
			if ($this->_lastID > 0) {
				return true;
			} else {
				$this->_lastID = null;
				return false;
			}
		} else {
			$this->_lastID = null;
			return false;
		}
    }

    public function update($table, $fields, $values, $whereCondition = null, $dataTypes = null)
    {
	
        $placeholders = array();
        $updateValues = array();

        if (is_array($dataTypes)) {
            $bindDataTypes = array();
        } else {
            $bindDataTypes = $dataTypes;
        }

        /**
         * Objects are casted using __toString, null values are converted to string 'null', everything else is passed as '?'
         */
        foreach ($values as $position => $value) {

            if (!isset($fields[$position])) {
                throw new \Phalcon\Db\Exception("The number of values in the update is not the same as fields");
            }
            $field = $fields[$position];

            if (false){//globals_get("db.escape_identifiers") {
                $escapedField = $this->escapeIdentifier($field);
            } else {
                $escapedField = $field;
            }

            if (is_object($value)) {
                // $placeholders[] = $escapedField . " = " . $value;
				$placeholders[] = $escapedField . ' = ? ';
				$updateValues[] = (string) $value;
            } else {
                if ($value === null) { // (0 ==) null is true
                    $placeholders[] = $escapedField . " = null";
					// $placeholders[] = $escapedField . ' = ? ';
					// $updateValues[] = null;
                } else {
                    $updateValues[] = $value;
                    if (is_array($dataTypes)) {
                        if (!isset($dataTypes[$position])) {
                            throw new \Phalcon\Db\Exception("Incomplete number of bind types");
                        }
                        $bindType = $dataTypes[$position];
                        $bindDataTypes[] = $bindType;
                    }
                    $placeholders[] = $escapedField . " = ?";
                }
            }
        }

        if (false){//globals_get("db.escape_identifiers") {
            $escapedTable = $this->escapeIdentifier($table);
        } else {
            $escapedTable = $table;
        }


        $setClause = join(", ", $placeholders);

        if ($whereCondition !== null) {

            $updateSql = "UPDATE " . $escapedTable . " SET " . $setClause . " WHERE ";

            /**
             * String conditions are simply appended to the SQL
             */
            if (!is_array($whereCondition)) {
                $updateSql .= $whereCondition;
            } else {

                /**
                 * Array conditions may have bound params and bound types
                 */
                if (!is_array($whereCondition)) {
                    throw new \Phalcon\Db\Exception("Invalid WHERE clause conditions");
                }

                /**
                 * If an index 'conditions' is present it contains string where conditions that are appended to the UPDATE sql
                 */
                if (isset($whereCondition["conditions"])) {
                    $conditions = $whereCondition['conditions'];
                    $updateSql .= $conditions;
                }

                /**
                 * Bound parameters are arbitrary values that are passed by separate
                 */
                if (isset($whereCondition["bind"])) {
                    $whereBind = $whereCondition["bind"];
                    $updateValues = array_merge($updateValues, $whereBind);
                }

                /**
                 * Bind types is how the bound parameters must be casted before be sent to the database system
                 */
                if (isset($whereCondition["bindTypes"])) {
                    $whereTypes = $whereCondition['bindTypes'];
                    $bindDataTypes = array_merge($bindDataTypes, $whereTypes);
                }
            }
        } else {
            $updateSql = "UPDATE " . $escapedTable . " SET " . $setClause;
        }
		
        /**
         * Perform the update via PDO::execute
         */
        //					echo PHP_EOL . $updateSql;
        //					var_dump($updateValues);

        return $this->execute($updateSql, $updateValues, $bindDataTypes);
    }



    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        // $sql = 'SET NOCOUNT ON; SELECT CAST(SCOPE_IDENTITY() as int) as id';
		// echo __FUNCTION__.': '.$this->instance.'<br>'; die;
		return $this->_lastID;
        // return (int)$this->fetchOne($sql);
    }


    public function delete($table, $whereCondition = null, $placeholders = null, $dataTypes = null)
    {
        $sql;
        $escapedTable;

        if (false) { // globals_get("db.escape_identifiers") {
            $escapedTable = $this->escapeIdentifier($table);
        } else {
            $escapedTable = $table;
        }


        if (!empty($whereCondition)) {
            $sql = "DELETE FROM " . $escapedTable . " WHERE " . $whereCondition;
        } else {
            $sql = "DELETE FROM " . $escapedTable;
        }

        /**
         * Perform the update via PDO::execute
         */

        return $this->execute($sql, $placeholders, $dataTypes);
    }

    /**
     * Lists table indexes
     *
     *<code>
     *	print_r($connection->describeIndexes('robots_parts'));
     *</code>
     *
     * @param	string table
     * @param	string schema
     * @return	Phalcon\Db\Index[]
     */
    public function describeIndexes($table, $schema = null)
    {

        $dialect = $this->_dialect;

        $indexes = array();
        $temps = $this->fetchAll($dialect->describeIndexes($table, $schema), \Phalcon\Db::FETCH_ASSOC);
        foreach ($temps as $index) {
            $keyName = $index['index_id'];
            if (!isset($indexes[$keyName])) {
                $indexes[$keyName] = array();
            }

            //let indexes[keyName][] = index[4];
        }

        $indexObjects = array();
        foreach ($indexes as $name => $indexColumns) {

            /**
             * Every index is abstracted using a Phalcon\Db\Index instance
             */
            $indexObjects[$name] = new \Phalcon\Db\Index($name, $indexColumns);
        }

        return $indexObjects;
    }

    /**
     * Lists table references
     *
     *<code>
     * print_r($connection->describeReferences('robots_parts'));
     *</code>
     *
     * @param	string table
     * @param	string schema
     * @return	Phalcon\Db\Reference[]
     */
    public function describeReferences($table, $schema = null)
    {

        $dialect = $this->_dialect;

        $emptyArr = array();
        $references = array();

        $temps = $this->fetchAll($dialect->describeReferences($table, $schema), \Phalcon\Db::FETCH_NUM);
        foreach ($temps as $reference) {

            $constraintName = $reference[2];
            if (!isset($references[$constraintName])) {
                $references[$constraintName] = array(
                        "referencedSchema"  => $reference[3],
                        "referencedTable"   => $reference[4],
                        "columns"           => $emptyArr,
                        "referencedColumns" => $emptyArr
                        );
            }

            //let references[constraintName]["columns"][] = reference[1],
            //	references[constraintName]["referencedColumns"][] = reference[5];
        }

        $referenceObjects = array();
        foreach ($references as $name => $arrayReference) {
            $referenceObjects[$name] = new \Phalcon\Db\Reference($name, array(
                        "referencedSchema"	=> $arrayReference["referencedSchema"],
                        "referencedTable"	=> $arrayReference["referencedTable"],
                        "columns"			=> $arrayReference["columns"],
                        "referencedColumns" => $arrayReference["referencedColumns"]
                        ));
        }

        return $referenceObjects;
    }

    /**
     * Gets creation options from a table
     *
     *<code>
     * print_r($connection->tableOptions('robots'));
     *</code>
     *
     * @param	string tableName
     * @param	string schemaName
     * @return	array
     */
    public function tableOptions($tableName, $schemaName = null)
    {
        $dialect = $this->_dialect;
        $sql = $dialect->tableOptions($tableName, $schemaName);
        if ($sql) {
            $describe = $this->fetchAll($sql, \Phalcon\DB::FETCH_NUM);
            return $describe[0];
        }
        return array();
    }

    /**
     * Creates a PDO DSN for the adapter from $this->_config settings.
     *
     * @return string
     */
    protected function _dsn()
    {
        // baseline of DSN parts
        $dsn = $this->_config;

        // don't pass the username and password in the DSN
        unset($dsn['username']);
        unset($dsn['password']);
        unset($dsn['options']);
        unset($dsn['persistent']);
        unset($dsn['driver_options']);

        if (isset($dsn['port'])) {
            $seperator = ':';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $seperator = ',';
            }
            $dsn['host'] .= $seperator . $dsn['port'];
            unset($dsn['port']);
        }

        // this driver supports multiple DSN prefixes
        // @see http://www.php.net/manual/en/ref.pdo-dblib.connection.php
        if (isset($dsn['pdoType'])) {
            switch (strtolower($dsn['pdoType'])) {
                case 'freetds':
                case 'sybase':
                    $this->_pdoType = 'sybase';
                    break;
                case 'mssql':
                    $this->_pdoType = 'mssql';
                    break;
                case 'dblib':
                default:
                    $this->_pdoType = 'dblib';
                    break;
            }
            unset($dsn['pdoType']);
        }

        // use all remaining parts in the DSN
        foreach ($dsn as $key => $val) {
            $dsn[$key] = "$key=$val";
        }

        $dsn = $this->_pdoType . ':' . implode(';', $dsn);
        return $dsn;
    }


    /**
     * Begin a transaction.
     *
     * It is necessary to override the abstract PDO transaction functions here, as
     * the PDO driver for MSSQL does not support transactions.
     */
    public function begin($nesting = false)
    {
        //						$this->execute('SET QUOTED_IDENTIFIER OFF');
        //						$this->execute('SET NOCOUNT OFF');
        $this->execute('BEGIN TRANSACTION;');
        return true;
    }

    /**
     * Commit a transaction.
     *
     * It is necessary to override the abstract PDO transaction functions here, as
     * the PDO driver for MSSQL does not support transactions.
     */
    public function commit($nesting = false)
    {
        $this->execute('COMMIT TRANSACTION');
        return true;
    }

    /**
     * Roll-back a transaction.
     *
     * It is necessary to override the abstract PDO transaction functions here, as
     * the PDO driver for MSSQL does not support transactions.
     */
    public function rollBack($nesting = false)
    {
        $this->execute('ROLLBACK TRANSACTION');
        return true;
    }

    public function getTransactionLevel()
    {
        return (int)$this->fetchOne('SELECT @@TRANCOUNT as level');
    }
}