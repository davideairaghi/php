<?php

namespace Airaghi\Tools;

/*
 * AutoLoad  a class to setup auto loading of classes, interfaces, traits
 * @notes   namespace is used to identify directories, name is used to identify filesize
 *          every string is case sensitive and file names must have ".php" extension
 */
class AutoLoad {

	/*
	 * directory list, in order of priority
	 * @var array
	 */	 
	static protected $dirs = array();
	
	/*
	 * set dirs to use when searching for classes, interfaces, traits
	 * @param array $dirs
	 */
	public static function setDirs($dirs=array()) {
		if ($dirs === null || $dirs === '' || (is_array($dirs)&&!$dirs)) {
			$dirs = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
		}
		if (!is_array($dirs)) {
			$dirs = array(strval($dirs));
		}
		if (!static::$dirs && $dirs) {
			static::$dirs = $dirs;
		}
	}

	/*
	 * enable autoloading of classes, interfaces, traits
	 * @param array $dirs
	 */	
	public static function enableAutoLoad($dirs=array()) {
		static::setDirs($dirs);
		spl_autoload_register(array('\\Airaghi\\Tools\\AutoLoad','search'),true,true);
	}
	
	/*
	 * search for a specific class/interface/trait and load the file containing it
	 * @param string $name
	 */
	public static function search($name) {
		$name  = str_replace('\\',DIRECTORY_SEPARATOR,$name);
		$name .= '.php';
		foreach (static::$dirs as $dir) {
			$fileName = str_replace( DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR , DIRECTORY_SEPARATOR , $dir . DIRECTORY_SEPARATOR . $name);
			if (file_exists($fileName)) {
				require_once($fileName);
			}
		}
	}
	
}

?>