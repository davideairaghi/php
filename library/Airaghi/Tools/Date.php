<?php

namespace Airaghi\Tools;

/**
 * classe per la gestione di date
 *
 * @author Davide Airaghi
 */
class Date {

    /**
     * ritorna una data vuota per il database
     * @param boolean $forceNull se true ritorna null
     * @return string|null
     */
    static function dbDateEmpty($forceNull = false) {
        if ($forceNull) {
            return null;
        }
        return "0000-00-00";
    }

    /**
     * ritorna una data vuota in formato italiano
     * @param boolean $forceNull se true ritorna null
     * @return string|null
     */
    static function itaDateEmpty($forceNull = false) {
        if ($forceNull) {
            return null;
        }
        return "00-00-0000";
    }

    /**
     * ritorna una data+ora vuota per il database
     * @param boolean $forceNull se true ritorna null
     * @return string|null
     */
    static function dbDateTimeEmpty($forceNull = false) {
        if ($forceNull) {
            return null;
        }
        return "0000-00-00 00:00:00";
    }

    /**
     * ritorna una data+ora in formato italiano
     * @param boolean $forceNull se true ritorna null
     * @return string|null
     */
    static function itaDateTimeEmpty($forceNull = false) {
        if ($forceNull) {
            return null;
        }
        return "00-00-0000 00:00:00";
    }

    /**
     * ritorna la data attuale in formato db
     * @return string
     */
    static function dbDateNow() {
        return self::date("Y-m-d");
    }

    /**
     * ritorna la data attuale in formato italiano
     * @return string
     */
    static function itaDateNow() {
        return self::toItaDate(self::dbDateNow());
    }

    /**
     * ritorna la data+ora attuale in formato db
     * @return string
     */
    static function dbDateTimeNow() {
        return self::date("Y-m-d H:i:s");
    }

    /**
     * ritorna la data+ora attuale in formato italiano
     * @return string
     */
    static function itaDateTimeNow() {
        return self::toItaDateTime(self::dbDateTimeNow());
    }

    /**
     * converte la data passata in formato db
     * @param string $data la data da convertire
     * @param string $sep il separatore da utilizzare
     * @return string|boolean
     */
    static function toDbDate($data, $sep = '-') {
        if (self::isDbDate($data)) {
            return $data;
        }
		$data = str_replace( array('.','-','/'), array($sep,$sep,$sep), $data );
        $data = substr($data, 0, 10);
        $data = substr("0000" . intval(substr($data, 6, 4)), -4) .
                $sep .
                substr("00" . intval(substr($data, 3, 2)), -2) .
                $sep .
                substr("00" . intval(substr($data, 0, 2)), -2);
        if ($data < '1900'.$sep.'01'.$sep.'01') {
            return false;
        }
        $parti = explode($sep, $data);
        if (count($parti) != 3) {
            return false;
        }
        if (!is_numeric($parti[0]) || strlen($parti[0]) != 4) {
            return false;
        }
        if (!is_numeric($parti[1]) || strlen($parti[1]) != 2) {
            return false;
        }
        if (!is_numeric($parti[2]) || strlen($parti[2]) != 2) {
            return false;
        }
        return $data;
    }

    /**
     * converte la data passata in formato italiano
     * @param string $data la data da convertire
     * @param string $sep il separatore da utilizzare
     * @return string|boolean
     */    
    static function toItaDate($data, $sep = '-') {
        if (!self::isDbDate($data)) {
            return $data;
        }
        $data = substr($data, 0, 10);		
        return substr("00" . intval(substr($data, 8, 2)), -2) .
                $sep .
                substr("00" . intval(substr($data, 5, 2)), -2) .
                $sep .
                substr("0000" . intval(substr($data, 0, 4)), -4);
    }

    /**
     * converte la data passata in un formato in funzione della lingua indicata
     * @param string $data la data da convertire
     * @param string $lang ita|eng
     * @param string $sep il separatore da utilizzare
     * @return string|boolean
     */
    static function toLangDate($data, $lang = 'eng', $sep = '/') {
        switch ($lang) {
            case 'eng':
                return self::toDbDate($data);
            case 'ita':
                return self::toItaDate($data, $sep);
        }
    }

    /**
     * ritorna true se la data e' in formato db
     * @param string $d data da controllare
     * @return boolean
     */
    static function isDbDate($d) {
        $ok = preg_match("/^([0-9]{4})[-\/]([0-9]{2})[-\/]([0-9]{2})$/", $d);
        if ($ok) {
            try {
                $d1 = new \DateTime($d,new \DateTimeZone(\date_default_timezone_get()));
                if ($d1->format('Y') <= 0 || $d1->format('m') <= 0 || $d1->format('d') <= 0) {
                    $ok = false;
                }
            } catch (\Exception $ex) {
                $ok = false;
            }
        }
        return $ok;
    }

    /**
     * ritorna true se la data+ora e' in formato db
     * @param string $d data da controllare
     * @return boolean
     */    
    static function isDbDateTime($d) {
        $ok = preg_match("/^([0-9]{4})[-\/]([0-9]{2})[-\/]([0-9]{2})\ [0-9]{2}:[0-9]{2}:[0-9]{2}$/", $d);
        if ($ok) {
            try {
                $d1 = new \DateTime($d,new \DateTimeZone(\date_default_timezone_get()));
                if ($d1->format('Y') <= 0 || $d1->format('m') <= 0 || $d1->format('d') <= 0) {
                    $ok = false;
                }
            } catch (\Exception $ex) {
                $ok = false;
            }
        }
        return $ok;
    }

     /**
     * ritorna true se la data e' in formato italiano
     * @param string $d data da controllare
     * @return boolean
     */
    static function isItaDate($d) {
        $ok = preg_match("/^([0-9]{2})[-\/]([0-9]{2})[-\/]([0-9]{4})$/", $d);
        if ($ok) {
            $ok = self::isDbDate(self::toDbDate($d));
        }
        return $ok;
    }

    /**
     * ritorna true se la data+ora e' in formato italiano
     * @param string $d data da controllare
     * @return boolean
     */    
    static function isItaDateTime($d) {
        $ok = preg_match("/^([0-9]{2})[-\/]([0-9]{2})[-\/]([0-9]{4})\ [0-9]{2}:[0-9]{2}:[0-9]{2}$/", $d);
        if ($ok) {
            $ok = self::isDbDateTime(self::toDbDateTime($d));
        }
        return $ok;
    }

    /**
     * ritorna true se la data e' vuota
     * @param string $data data da controllare
     * @return boolean
     */
    static function isEmptyDate($data) {
        if (is_null($data) || $data == '') {
            return true;
        }
        if (self::isDbDate($data)) {
            if ($data == self::dbDateEmpty()) {
                return true;
            }
        }
        if (self::isItaDate($data)) {
            if ($data == self::itaDateEmpty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * ritorna true se la data+ora e' vuota
     * @param string $data data da controllare
     * @return boolean
     */    
    static function isEmptyDateTime($data) {
        if (is_null($data) || $data == '') {
            return true;
        }
        if (self::isDbDateTime($data)) {
            if ($data == self::dbDateTimeEmpty()) {
                return true;
            }
        }
        if (self::isItaDateTime($data)) {
            if ($data == self::itaDateTimeEmpty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * ritorna la data senza l'orario
     * @param string $data data da convertire
     * @return string
     */    
    static function stripTime($data) {
        return substr($data, 0, 10);
    }

    /**
     * ritorna l'orario senza la data
     * @param string $data data da convertire
     * @return string
     */        
    static function stripDate($data) {
        if (strlen($data) > 10) {
            return substr($data, -8);
        } else {
            return null;
        }
    }

    /**
     * ritorna data+ora in formato db
     * @param  string $dataora data da convertire
     * @return string
     */        
    static function toDbDateTime($dataora) {
        if (self::isDbDate(substr($dataora, 0, 10))) {
            return $dataora;
        }
        return substr($dataora, 6, 4) . "-" . substr($dataora, 3, 2) . "-" . substr($dataora, 0, 2) . " " . substr($dataora, 11, 8);
    }

    /**
     * ritorna data+ora in formato db e fuso orario utc
     * @param  string $dataora data da convertire
     * @param  string $timezone la timezone di partenza, vuota se va usata quella di default
     * @return string
     */            
    static function toDbDateTimeUTC($dataora, $timezone = '') {
        return self::toDbDateTime(self::dbTimezoneDateTime($dataora, $timezone, 'UTC'));
    }

    /**
     * ritorna data+ora in formato italiano
     * @param  string $dataora data da convertire
     * @param  string $s il separatore tra giorno, mese e anno
     * @return string
     */            
    static function toItaDateTime($dataora, $s = "-") {
        if (!self::isDbDate(substr($dataora, 0, 10))) {
            return $dataora;
        }
        return substr($dataora, 8, 2) . $s . substr($dataora, 5, 2) . $s . substr($dataora, 0, 4) . " " . substr($dataora, 11, 8);
    }

    /**
     * verifica se due date sono uguali, a livello di giorno-mese-anno
     * @param  string $d1 prima data
     * @param  string $d2 seconda data
     * @return string
     */            
    static function equals($d1, $d2) {
        $d1 = trim($d1);
        $d2 = trim($d2);
        $d1 = str_replace('/', '-', $d1);
        $d2 = str_replace('/', '-', $d2);
        $d1 = self::toDbDate($d1);
        $d2 = self::toDbDate($d2);
        return ($d1 == $d2);
    }

    /**
     * ritorna la data della pasqua per l'anno indicato, in formato italiano
     * @param  int $anno anno
     * @return string
     */
    public static function getPasqua($anno) {

        $anno = intval($anno);

        $Y = date('Y');

        if ($anno > 0) {
            $Y = $anno;
        } else {
            $anno = $Y;
        }

        if ($Y < 2099) {
            $M = 24;
            $N = 5;
        } elseif ($Y < 2199) {
            $M = 24;
            $N = 6;
        } elseif ($Y < 2299) {
            $M = 25;
            $N = 0;
        } elseif ($Y < 2399) {
            $M = 26;
            $N = 1;
        } elseif ($Y < 2499) {
            $M = 25;
            $N = 1;
        }

        $a = $Y % 19;
        $b = $Y % 4;
        $c = $Y % 7;
        $d = ((19 * $a) + $M) % 30;
        $e = ((2 * $b) + (4 * $c) + (6 * $d) + $N) % 7;

        if ($d + $e < 10) {
            $giorno = $d + $e + 22;
            $mese = 3;
        } else {
            $giorno = $d + $e - 9;
            $mese = 4;
        }
        if ($giorno == 26 && $mese == 4) {
            $giorno = 19;
            $mese = 4;
        }

        if ($giorno == 25 && $mese == 4 && $d == 28 && $e == 6 && $a > 10) {
            $giorno = 18;
            $mese = 4;
        }

        $giorno = substr("00" . $giorno, -2);
        $mese = substr("00" . $mese, -2);

        return self::toItaDate($giorno . "-" . $mese . "-" . $anno);
    }

    /**
     * controlla se la data indicata corrisponde ad una festivita' italiana
     * @param string $data data da verificare
     * @return boolean
     */
    public static function isFestivo($data) {

        $dataita = self::toItaDate($data);

        $index = 0;

        $date_festa = array("01/01", "06/01", "25/04", "01/05", "02/06", "15/08", "01/11", "08/12", "25/12", "26/12");

        if ($dataita == "") {
            return false;
        }

        $data = self::toDbDate($data);
        $tdata = strtotime($data);

        $anno = date('Y', $tdata);
        $mese = date('m', $tdata);
        $giorno = date('d', $tdata);

        $tmp = getdate($tdata);

        if ($tmp['wday'] == 0) {
            return true;
        }

        $tmp = $giorno . "/" . $mese;

        foreach ($date_festa as $dfesta) {
            if ($dfesta == $tmp) {
                return true;
            }
        }

        $pasqua = self::getPasqua($anno);

        if (self::toDbDate($pasqua) == self::toDbDate($tmp . "/" . $anno)) {
            return true;
        }

        $pasquetta = strtotime(self::toDbDate($pasqua)) + (3600 * 24);
        $pasquetta = self::toDbDate(date('Y-m-d', $pasquetta));

        if ($pasquetta == self::toDbDate($tmp . "/" . $anno)) {
            return true;
        }

        return false;
    }

    /**
     * ritorna il giorno non festivo, in formato db, precedente a quello indicato
     * @param  string $data data di riferimento
     * @return string
     */
    public static function prevNonFestivo($data) {

        $data = self::toDbDate($data);
        $tdata = strtotime($data);

        $tmp = array();
        $tmp[0] = date('d', $tdata);
        $tmp[1] = date('m', $tdata);
        $tmp[2] = date('Y', $tdata);

        $ok = false;

        $giorno = $mese = "00";
        $anno = "0000";

        while (!$ok) {
            $tdata = $tdata - (3600 * 24);
            $data = self::toDbDate(date('Y-m-d', $tdata));
            $mese = date('m', $tdata);
            $giorno = date('d', $tdata);
            $anno = date('Y', $tdata);
            if (!self::isFestivo($giorno . "/" . $mese . "/" . $anno)) {
                $ok = true;
            }
        }

        return self::toDbDate($giorno . "/" . $mese . "/" . $anno);
    }

    /**
     * converte in formato database una data da una timezona ad un'altra
     * @param string $dt data+ora da convertire
     * @param string $timezone timezone di origine, se vuoto viene usata quella di default
     * @param string $timezone_new nuova timezone, se vuoto viene usata quella di default
     * @return string
     */
    public static function dbTimezoneDateTime($dt, $timezone = '', $timezone_new = '') {
        if (!$timezone) {
            $timezone = date_default_timezone_get();
        }
        if (!$timezone_new) {
            $timezone = date_default_timezone_get();
        }
        $timezone = new \DateTimeZone($timezone);
        $timezone_new = new \DateTimeZone($timezone_new);
        $dt = self::toDbDateTime($dt);
        $dt = new \DateTime($dt, $timezone);
        $dt->setTimeZone($timezone_new);
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * converte in formato italiano una data da una timezona ad un'altra
     * @param string $dt data+ora da convertire
     * @param string $timezone timezone di origine, se vuoto viene usata quella di default
     * @param string $timezone_new nuova timezone, se vuoto viene usata quella di default
     * @return string
     */    
    public static function itaTimezoneDateTime($dt, $timezone = '', $timezone_new = '') {
        if (!$timezone) {
            $timezone = date_default_timezone_get();
        }
        if (!$timezone_new) {
            $timezone = date_default_timezone_get();
        }
        $timezone = new \DateTimeZone($timezone);
        $timezone_new = new \DateTimeZone($timezone_new);
        return self::toItaDateTime(self::dbTimezoneDateTime($dt, $timezone, $timezone_new));
    }

	/*
	 * ritorna lo unix time attuale, non è afflitta dal bug delle date
	 * a 64bit presente su windows !
	 * @return float
	 */
	public static function time() {
		if (floatval(PHP_INT_MAX) < 9223372036854775807.00) {
			$dt = new \DateTime('now',new \DateTimeZone(\date_default_timezone_get()));
			$dt->setTimezone(new \DateTimeZone(\date_default_timezone_get()));
			$tm = floatval($dt->format('U'));
			return $tm;
		} else {
			return \time();
		}
	}
	
	/*
	 * ritorna lo unix time riferito alla data indicata
	 * non è afflitta dal bug delle date a 64bit presente su windows !
	 * @param string $dt data
	 * @return float
	 */
	public static function strtotime($dt) {
		if (self::isItaDate($dt)) {
			$dt = self::toDbDate($dt).' 00:00:00';
		} elseif (self::isItaDateTime($dt)) {
			$dt = self::toDbDateTime($dt);
		}
		if (strlen($dt)<=10) {
			$dt .= ' 00:00:00';
		}
		$dt = str_replace('/','-',$dt);
		if (floatval(PHP_INT_MAX) < 9223372036854775807.00) {
			try {
				$data = new \DateTime($dt,new \DateTimeZone(\date_default_timezone_get()));
				$data->setTimezone(new \DateTimeZone(\date_default_timezone_get()));
				return floatval($data->format('U'));
			} catch (Exception $ex) {
				return false;
			}
		} else {
			return strtotime($dt);
		}		
	}
	
	/*
	 * ritorna una data formatatta secondo le specifiche ricevute
	 * @param string $format formato data
	 * @param float  $time   unixtimestamp
	 * @return string
	 */
	public static function date($format,$time=false) {
		if (floatval(PHP_INT_MAX) < 9223372036854775807.00) {
			$dt   = \DateTime::createFromFormat('U',strval($time!==false?$time:self::time()),new \DateTimeZone(\date_default_timezone_get()));
			$dt->setTimezone(new \DateTimeZone(\date_default_timezone_get()));
			return $dt->format($format);
		} else {
			return date($format,($time!==false ? $time : self::time()));
		}
	}
	
	/*
	 * ritorna il numero di giorni che occorrono per andare da $from a $to
	 * @param string $from data da cui cominciare a contare
	 * @param string $to   data a cui arrivare nel conteggio dei giorni
	 * @return float
	 */
	public static function diffDays($from,$to) {
		try {
			$a = self::strtotime( self::toDbDate(substr($from,0,10)) );
			$b = self::strtotime( self::toDbDate(substr($to,0,10))   );
			$giorni = floatval($b - $a) / floatval(3600*24);
			return $giorni;
		} catch (Exception $ex) {
			return false;
		}
	}

	/*
	 * ritorna il numero di anni che separano $from e $to
	 * @param string  $from data da cui cominciare a contare
	 * @param string  $to   data a cui arrivare nel conteggio dei giorni
	 * @param boolean $invert indica $from è successivo a $to
	 * @return float
	 */	
	public static function diffYears($from,$to,&$invert=false) {
		try {
			$a = \Airaghi\Tools\Date::toDbDate( substr($from,0,10) );
			$b = \Airaghi\Tools\Date::toDbDate( substr($to,0,10) );
			$a = new \DateTime($a);
			$b = new \DateTime($b);
			$diff   = $a->diff($b);
			if ($diff->invert > 0) {
				$invert = true;
				return (-1) * $diff->y;
			} else {
				$invert = false;
				return $diff->y;
			}
		} catch (Exception $ex) {
			return false;
		}	
	}
	
	/*
	 * verifica se una stringa rappresenta un valido orario,
	 * i formati accettati sono hh:mm  e hh:mm:ss
	 * @param  string  $t stringa del "tempo"
	 * @return string  stringa rappresentante hh:mm:ss o false in caso di errore
	 */
	public static function checkTime($t) {
		$t = strval($t);
		$t = explode(':',$t);
		if (count($t) == 2) {
			$hh = $t[0];
			$mm = $t[1];
			if (strlen($hh)==2 && strlen($mm)==2 && $hh >= '00' && $hh <= '23' && $mm >= '00' && $mm <= '59'  && is_numeric($mm) && is_numeric($hh)) {
				return $hh.':'.$mm.':00';
			} else {
				return false;
			}
		} elseif (count($t) == 3) {
			$hh = $t[0];
			$mm = $t[1];
			$ss = $t[2];
			if (strlen($hh)==2 && strlen($mm)==2 && strlen($ss)==2 && $hh >= '00' && $hh <= '23' && $mm >= '00' && $mm <= '59' && $ss >= '00' && $ss <= '59') {
				return $hh.':'.$mm.':'.$ss;
			} else {
				return false;
			}
		} else {
			return false;
		}
		return false;
	}

	/*
	 * ritorna una data in formato iso
	 * @param  string $d data
	 * @param  boolean $force
	 * @return string
	 */
	public static function toISODate($d,$force=true,$autodetect=true) {
		$date = true; 
		$datetime = true;
		if ($force) {
			$d        = substr($d,0,10);
			$datetime = false;
		}
		if ($autodetect) {
			if (strlen($d)>10) { 
				$date     = false;
			} else {
				$datetime = false;
			}
		}
		if ($datetime) {
			if (!\Airaghi\Tools\Date::isItaDateTime($d) && !\Airaghi\Tools\Date::isDbDateTime($d)) {;
				return '';
			}
			$d = \Airaghi\Tools\Date::toDbDateTime($d);
			$d = str_replace(array('-','/'),array('-','-'),$d);
			$d = str_replace(array(' ','.'),array('T',':'),$d);
			if (strlen($d) <= 19) {
				$d = $d . '.000';
			}
			return $d;
		} elseif ($date) {
			if (!\Airaghi\Tools\Date::isItaDate($d) && !\Airaghi\Tools\Date::isDbDate($d)) {;
				return '';
			}
			$d = \Airaghi\Tools\Date::toDbDate($d);
			$d = str_replace(array('/','-','.'),'-',$d);
			$d = $d.'T00:00:00.000';
		}
		return $d;
	}

	/*
	 * ritorna se una data in formato iso
	 * @param  string $d data
	 * @return string
	 */
	public static function isISODate($d) {
		$isodate     = '#^[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$#';
		$isodatetime = '#^[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]T[0-9][0-9]\:[0-9][0-9]\:[0-9][0-9]\.([0-9]+)$#';
		return (preg_match($isodate,$d) || preg_match($isodatetime,$d));
	}	

	/*
	 * ritorna una data in formato che era in iso
	 * @param  boolean $force_date
	 * @param  string  $lang
	 * @return string
	 */
	public static function fromISODate($d,$force_date=true,$lang='ita') {	
		$date = $datetime = false;
		$call = ($lang == 'ita' ? 'Ita' : 'Db');
		$call = 'to'.$call.'Date';
		if ($force_date) {
			$d = substr($d,0,10);
		}
		if (strlen($d)<=10) {
			$date = true;
		} else {
			$datetime = true;
		}
		$d = str_replace('T',' ',$d);
		$d = preg_replace('#\.([0-9]+)$#','',$d);
		$parti = explode(' ',$d);
		$parti[0] = substr($parti[0],0,4).'-'.substr($parti[0],4,2).'-'.substr($parti[0],-2);
		$parti[0] = \Airaghi\Tools\Date::$call( $parti[0] );
		$d = implode(' ',$parti);
		return $d;
	}
	
}
