<?php

namespace Airaghi\Tools;

require_once(__DIR__.'/_phpmailer/class.phpmailer.php');

class Mail extends \PHPMailer {
    
  static private $mail = null;
  
  /*
   * permette di caricare un template di mail e sostituire i placeholder
   * che saranno del tipo [[VAR]]
   * @param string $template
   * @param array  $dati
   * @param \ControllerBase $controller
   * @return string
   */
  static function compileTemplate($template,$dati=array(),&$controller=null) {
		if ($controller) {
			// se ci viene passato un controller lo usiamo per dedurre l'url base del sito, altrimenti il dato deve essere inviato dal chiamante in $dati
			$dati['BASEURL'] = ($controller->request->getServer('HTTPS') == 'off' ? 'http://' : 'https://') . $controller->request->getServer('SERVER_NAME') . '' ;
		}
		$dir  = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 
				'html' . DIRECTORY_SEPARATOR . 'template_email' . DIRECTORY_SEPARATOR;
		$file = $dir . $template;
		if (!file_exists($file)) {
			return false;
		}
		$file = file_get_contents($file);
		foreach ($dati as $k=>$v) {
			$k = strtoupper($k);
			$file = str_replace( '[['.$k.']]', $v, $file );
		}
		return $file;
  }
  
  static function deliver($from, $fromname, $to, $subject, $text, $html_text="", $cc="", $bcc="", $extra_headers="", $attachs=array()) {
		
                // return true;
                
                // inserire i propri dati per il server smtp
		if (!self::$mail) {
			self::$mail = new \PHPMailer();
			self::$mail->SMTPAuth = false;
			self::$mail->IsSMTP();
			self::$mail->Mailer   = 'smtp';
			self::$mail->Host     = 'mail.server.it';
		}
				
		self::$mail->From     = $from;
		self::$mail->FromName = $fromname;
		
		// ob_start();
		
		self::$mail->ClearAddresses();
		self::$mail->ClearAllRecipients();
		self::$mail->ClearAttachments();
		self::$mail->ClearBCCs();
		self::$mail->ClearCCs();
		self::$mail->ClearCustomHeaders();
		self::$mail->ClearReplyTos();
	
		if (!is_array($to)) {
			$to = explode(',',$to);
		}
		if (!is_array($to)) { $to = array(); }
		foreach ($to as $indirizzo) {
			$indirizzo = trim($indirizzo);
                        if ($indirizzo) { self::$mail->AddAddress($indirizzo); }
		}
		
		if (trim($cc)!='') {
			$cc = explode(",",$cc);			
		}
                if (!is_array($cc)) { $cc = array(); }
		foreach ($cc as $indirizzo) {
			$indirizzo = trim($indirizzo);
                        if ($indirizzo) { self::$mail->AddCC($indirizzo); }
		}
		
		if (trim($bcc)!='') {
			$bcc = explode(",",$bcc);
		}
                if (!is_array($bcc)) { $bcc = array(); }
		foreach ($bcc as $indirizzo) {
			$indirizzo = trim($indirizzo);
                        if ($indirizzo) { self::$mail->AddBCC($indirizzo); }
		}
		
                if (!is_array($attachs)) { $attachs = array(); }
		foreach ($attachs as $file) {
                        if (file_exists($file)) { self::$mail->AddAttachment($file); }
		}
		
		if ($extra_headers != "") {
			self::$mail->AddCustomHeader($extra_headers);
                }
                
		self::$mail->Subject = $subject;
		
		self::$mail->CharSet = 'UTF-8';
		
		self::$mail->IsHTML($html_text != "" ? true : false);
		
		self::$mail->AltBody = ($html_text != "" ? $text : "");
		
		self::$mail->Body = ($html_text != "" ? $html_text : $text);
		
		// self::$mail->SMTPDebug = true;
		
		$ok =  self::$mail->Send();
		
		// ob_end_clean();
		
		return $ok;
	
    }
    
    static public function checkAddress($value) {
        $value = strtolower($value);
        if (strval(filter_var($value, FILTER_VALIDATE_EMAIL)) == '') {
            return false;
        }
        if (!preg_match('/^[a-z0-9][_\.a-z0-9-]+@([a-z0-9][0-9a-z-]+\.)+([a-z]{2,4})$/',$value)) {
            return false;
        }
        return true;
    }
	
}