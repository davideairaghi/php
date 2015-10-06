<?php

namespace Airaghi\Tools;

/**
 * classe di utility generiche
 * 
 * @author Davide Airaghi
 */
class Utils {

    /**
     * ritorna l'input con tutte le entità html "pulite", sfruttando la codifica utf-8
     * @param  string $v
     * @return string
     */
    public static function mb_htmlentities($v) {
        return htmlentities($v, ENT_COMPAT, 'UTF-8');
    }
    
    /**
     * mette a video, in "pre" se output in html, la variabile indicata
     * @param string $var variabile da debuggare
     * @param boolean $html indica se usare "<pre>" e "</pre>"
     */
    public static function debug($var, $html = true) {
        if ($html) { echo '<pre>'; }
        print_r($var);
        if ($html) { echo '</pre>'; }
    }

    /**
     * sostituisce i vari placeholder %s in una stringa
     * @param string $str
     * @param array  $ph
     * @return string
     */
    public static function replaceStringPlaceholders($str,$ph) {
        foreach ($ph as $p) {
            $str = preg_replace('/\%s/',$p,$str,1);
        }
        return $str;        
    }
    
    /**
     * ritorna, se presente, il valore del primo campo/proprietà in $var preso dalla lista in $list
     * @param  array $list lista di campi da verificare
     * @param  array|object $var variabile da controllare
     * @return boolean
     */
    private static function removeHidden_loop($list, $var) {
        // echo '<pre>'; print_r($list); print_r($var); echo '</pre>';
        $ret = '';
        foreach ($list as $el) {
            if (is_array($var)) {
                if (isset($var[$el])) {
                    $ret = $var[$el];
                    break;
                }
           } elseif (is_object($var)) {
                if (property_exists($var, $el)) {
                    $ret = $var->$el;
                    break;
                }
            }
        }
        // echo "---&gt; ".$ret."<br><br>";
        if ($ret !== '') {
            return $ret;
        }
        return false;
    }

    /**
     * accetta in input un array|object e ritorna solo gli elementi realmente attivi
     * @param object|array $vars è l'insieme dei valori 
     * @param string  $data indica quale data considerare nel confronto con "oggi"
     * @param boolean $use_time indica se usare anche la componente "ora" della data
     * @return type
     */
    static function removeHidden($vars, $data = '', $use_time = false) {
        if (!is_array($vars)) {
            $vars = array($vars);
        }
        if ($data == '') {
            if ($use_time) {
                $data = \Airaghi\Tools\Date::date('Y-m-d H:i:s');
            } else {
                $data = \Airaghi\Tools\Date::date('Y-m-d');
            }
        }
        if ($use_time) {
            if (strlen($data) <= 10) {
                $data = substr($data, 0, 10) . ' 00:00:00';
            }
            $data = Date::toDbDateTime($data);
        } else {
            $data = Date::toDbDate($data);
        }
        // echo '<pre>'; print_r($vars); echo '</pre>';
        foreach ($vars as $k => $var) {
            $stato = 1;
            $dal = "0000-01-01" . ($use_time ? ' 00:00:00' : '');
            $al = "9999-12-31" . ($use_time ? ' 00:00:00' : '');
            if (is_array($var) || is_object($var)) {
                $val = self::removeHidden_loop(array('attivo', 'attiva', 'visibile'), $var);
                if ($val !== false) {
                    $stato = intval($val);
                }
                $val = self::removeHidden_loop(array('visibile_dal', 'attivo_dal', 'attiva_dal', 'attivo_da','attiva_da', 'visibile_da','visibiledal', 'attivodal', 'attivadal'), $var);
                if ($val !== false) {
                    if ($use_time) {
                        $val = mb_substr($val, 0, 19);
                        if (!\Airaghi\Tools\Date::isEmptyDateTime($val)) {
                            $dal = $val;
                        }
                    } else {
                        $val = mb_substr($val, 0, 10);
                        if (!\Airaghi\Tools\Date::isEmptyDate($val)) {
                            $dal = $val;
                        }
                    }
                }
                $val = self::removeHidden_loop(array('visibile_al', 'attivo_al', 'attiva_al', 'attivo_a','attiva_a', 'visibile_a', 'visibileal', 'attivoal', 'attivaal'), $var);
                if ($val !== false) {
                    if ($use_time) {
                        $val = mb_substr($val, 0, 19);
                        if (!\Airaghi\Tools\Date::isEmptyDateTime($val)) {
                            $al = $val;
                        }
                    } else {
                        $val = mb_substr($val, 0, 10);
                        if (!\Airaghi\Tools\Date::isEmptyDate($val)) {
                            $al = $val;
                        }
                    }
                }
            } else {
                $stato = 0;
            }
            // echo ''.$stato.' > 0 && !( '.$dal.' <= '.$data.' && '.$al.' >= '.$data.' )<br>';
            if ($stato > 0 && !($dal <= $data && $al >= $data)) {
                $stato = 0;
            }
            if ($stato == 0) {
                unset($vars[$k]);
            }
        }
        return $vars;
    }

    
    /**
     * ritorna una stringa priva di lettere accentate e di altri caratteri "strani"
     * @param  string $v stringa da elaborare
     * @return string
     */
    static public function creaStringaPiana($v) {
        $acc_min = array('à','è','é','ì','ò','ù');
        $no_acc  = array('a','e','e','i','o','u');
        $v = str_replace($acc_min,$no_acc,$v);
        $acc_max = array('À','È','É','Ì','Ò','Ù');
        $no_acc  = array('A','E','E','I','O','U');
        $v = str_replace($acc_max,$no_acc,$v);
        $v = preg_replace('/([^a-zA-Z]+)/','',$v);
        return $v;
    }

    /**
     * analizza una stringa e sostituisce una serie di caratteri che possono dare problemi
     * @param  string $xml stringa da "pulire"
     * @return string
     */
    public static function cleanXml($xml) {
        $str = $xml;
		$str = strval($str);
        $str = str_replace(array("‘","“","”","‘","’","`","–","…"),array("'",'"','"',"'","'","-","..."),$str);
        $str = str_replace("<","&lt;",$str);
		$str = str_replace(">","&gt;",$str);
		$str = str_replace('"',"&quot;",$str);
		$str = str_replace(array('à','è','é','ì','ò','ù'),array("a'","e'","e'","i'","o'","u'"),$str);
		$str = str_replace(array('À','È','É','Ì','Ò','Ù'),array("A'","E'","E'","I'","O'","U'"),$str);
        $str = str_replace(array('€','£','°'),array('EUR','GBP','^'),$str);
        // $str = str_replace();
        return $str;
    }

    /**
     * analizza una stringa e sostituisce tutti i caratteri strani con i corrispondenti html
     * @param  string $str
     * @return string
     */
    public static function cleanXmlValue($str) {
        $str = strval($str);
        $str = \Airaghi\Tools\Utils::mb_htmlentities($str);
        $str = str_replace("'"," ",$str);
        return $str;
    }    

    /**
     * ritorna un oggetto a partire da un array
     * @param  array $arr
     * @return \stdClass
     */
    public static function arrayToObject($arr) {
        $ret = new \stdClass();
        foreach ($arr as $k=>$v) {
            $ret->$k = $v;
        }
        return $ret;
    }

	/*
	 * generazione di una password casuale
	 * @return string
	 */
	static public function randomPassword() {
		$lettere = array( 'A', 'C', 'E', 'F', 'H', 'K', 'M', 'N', 'P', 'R', 'U', 'V', 'W', 'X', 'Y', 'Z' );
		$simboli = array( '-', '#', '.', '!', '?' );
		$numeri  = array( '2', '3', '4', '6', '7', '8', '9' );
		$pwd     =  $lettere[intval(rand(0,15))].$lettere[intval(rand(0,15))].$lettere[intval(rand(0,15))].$lettere[intval(rand(0,15))].
					$simboli[intval(rand(0,4))].
					$numeri[intval(0,6)].$numeri[intval(0,6)].$numeri[intval(0,6)].
					$simboli[intval(rand(0,4))].
					$lettere[intval(rand(0,15))].$lettere[intval(rand(0,15))];
		return $pwd;
	}

	/*
	 * rimuove da una stringa tutto ciò che può dare fastidio in 
	 * un nome di variabile da usare in javascript
	 * @param string $name
	 * @return string
	 */
	static public function cleanJsName($name) {
		$name = strval($name);
		$name = preg_replace('/([^a-zA-Z0-9_]+)/','',$name);
		return $name;
	}

	/*
	 * rimuove da una stringa tutto ciò che può dare fastidio in 
	 * un valore di variabile, di un dato tipo, da usare in javascript
	 * @param string $value
	 * @param string $type
	 * @return string
	 */
	static public function cleanJsValue($value,$type) {
		$type = strtolower($type);
		switch ($type) {
			case 'input':
			default:
				$value = str_replace(array("\r","\n","</"),'',$value);
				$value = str_replace('"',"''",$value);
				break;
		}
		return $value;
	}
	
	/*
	 * calcola la cifra di controllo di un codice Ean13
	 * @param string $code
	 * @return string
	 */
	static public function getCheckDigitEan13($code) {
		if (strlen($code)>12) {
			$code = substr($code,0,12);
		}
		$i = $tot = 0;
		$m = strlen($code);
		for ($i=$m-1;$i>=0;$i=$i-2) {
			$tot = $tot + (intval(substr($code,$i,1))*3);
		}
		for ($i=$m-2;$i>=0;$i=$i-2) {
			$tot = $tot + intval(substr($code,$i,1));
		}
		$x = 10 - ($tot % 10);
		if ($x == 10) { 
			$x = 0;
		}
		return $x;
	}
	
	/*
	 * invia in output uno stream di dati di un dato tipo
	 * @param string $stream nome file o stringa
	 * @param string $type   tipo di output richiesto
	 * @param string $name   nome del file da far scaricare
	 * @param boolean $load  indica se caricare un file o meno
	 */
	static public function sendStream($stream,$type,$name='',$load=true) {
		header("Pragma: public");
		header('Pragma: no-cache');		
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header('Expires: 0');
		header('Content-Type',$type);
		if ($name=='' && $load) {
			$name = basename($stream);
		} elseif ($name=='' && !$load) {
			$name = trim(\Airaghi\Tools\Date::time());
		}
		$name = trim($name);
		header("Content-Disposition: attachment; filename=\"".$name."\";" );
		if (!$load) {
			$size = strlen($stream);
		} else {
			$size = filesize($stream);
		}
		header('Content-Length: '.$size);
		if (!$load) {
			echo $stream;
		} else {
			readfile($stream);
		}
	}

	/*
	 * creazione di un hash generico
	 * @param string $val  valore di cui calcolare l'hash
	 * @param double $time unixtime della creazione dell'hash, viene già accodato all'hash
	 * @return string
	 */
	static public function genericHash($val,&$time) {
		$salt = 'kj12hd1212ff2fgvv21c21c';
		$time = \Airaghi\Tools\Date::time();
		return md5($salt.$val).$time;
	}
	
	
}
