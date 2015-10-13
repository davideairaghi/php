<?php

namespace Airaghi\Tools;

/*
 * simple class to manage shared data (objects,strings,...) between pages/function/methods in you script execution
 */
class Container {

	/*
	 * container for data copied here
	 * @var array 
	 */
	protected static $stored   = array();

	/*
	 * container for data shared
	 * @var array 
	 */	
	protected static $shared = array();
	
	
	/*
	 * remove all data shared or stored 
	 */
	static function removeAll() {
		static::$stored = array();
		static::$shared = array();
	}

	/*
	 * remove all data stored 
	 */	
	static function removeAllStored() {
		static::$stored = array();
	}

	/*
	 * remove all data shared
	 */
	static function removeAllShared() {
		static::$shared = array();
	}	
	
	/*
	 * remove single shared data
	 * @param string $var
	 */
	static function removeShared($var) {
		$var = strval($var);
		unset(static::$shared[$var]);
	}

	/*
	 * remove single stored data
	 * @param string $var
	 */	
	static function removeStored($var) {
		$var = strval($var);
		unset(static::$stored[$var]);		
	}

	/*
	 * remove single shared or stored data
	 * if the key exists in both lists this method removes data from both of them
	 * @param string $var
	 */	
	static function remove($var) {
		static::removeShared($var);
		static::removeStored($var);
	}
	
	/*
	 * add new data to the shared list
	 * @param string $name
	 * @param mixed  $var
	 */
	static function share($name,&$var) {
		static::$shared[$name] = &$var;
	}

	/*
	 * add new data to the stored list
	 * @param string $name
	 * @param mixed  $var
	 */	
	static function store($name,$var) {
		if (is_object($var)) {
			$var = clone $var;
		}
		static::$stored[$name] = $var;
	}

	/*
	 * return the value of the given stored data or null if not present
	 * @param string $name
	 * @return mixed 
	 */
	static function getStored($name) {
		if (!isset(static::$stored[$name])) {
			return null;
		}
		return static::$stored[$name];
	}

	/*
	 * return a reference to the given shared data or null if not present
	 * @param string $name
	 * @return mixed 
	 */	
	static function &getShared($name) {
		$ret = null;
		if (!isset(static::$shared[$name])) {
			return $ret;
		}
		return static::$shared[$name];
	}
	

}


?>