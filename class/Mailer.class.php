<?php
require_once("Mail/RFC822.php");
require_once("PEAR.php");
require_once (SITEROOT."/class/pearMail.class.php");

class Mailer {

    const FILESIZE_LIMIT =  10485760; /* 10 Mio */

	private $recipients;
	private $lastError;
	private $fichier;	
	private $dataAsFile;
	
	public function __construct(){
		$this->recipients = array();
		$this->fichier = array();
		$this->dataAsFile = array();
	}
	
	public function getLastError(){
		return $this->lastError;
	}
	
	public function addRecipient($recipient){
		if (! $this->isValidMail($recipient)){
			return false;
		}
		$this->recipients[] = $recipient;
		return true;
	}
	
	public function addFile($file){
		$this->fichier[] = $file;
	}
	
	public function addStringAsFile($filename,$data){
		$this->dataAsFile[] = array('filename'=>$filename,'data'=>$data);
	}
	
	public function addComplexRecipient($recipient){
		return $this->addRecipient($this->getNormalizedEmailAdresse($recipient));
	}
	
	public function isValidMail($mail){
		$mail_RFC822 = new Mail_RFC822();
		$lo_mail = $mail_RFC822->parseAddressList($mail, NULL, FALSE);
		if(PEAR::isError($lo_mail)){
    		return false;
		} elseif ($lo_mail[0]->host=='localhost'){
			return false;
		}
		return true;
	}

  public function sendMail($subject, $body) {
  	
  	
  	assert(!!$subject);
  	assert(!!$body);
  	assert(!!$this->recipients);
  	 	
  	foreach ($this->recipients as $recipient) {
  		$crlf="\n";
		$mime = new Mail_mime($crlf);
		$mime->setTXTBody($body);
	
		$hdrs = array(
              	'From'    => TDT_FROM_EMAIL,
              	'Subject' => $subject,
            );

		foreach($this->fichier as $file){
		    if (filesize($file) < self::FILESIZE_LIMIT) {
                $mime->addAttachment($file);
            }
		}
            
		foreach($this->dataAsFile as $dataAsFile){
			$mime->addAttachment($dataAsFile['data'], 'application/octet-stream',$dataAsFile['filename'],false);
		}
        //do not ever try to call these lines in reverse order
		$body = $mime->get();
		$hdrs = $mime->headers($hdrs);
		
		$mail =new pearMail();
		$mail->sep = $crlf;
  		if (!$mail->send($recipient, $hdrs, $body,'')) {
  			$this->lastError = "Erreur lors de l'envoi d'un message vers $recipient" ;
        	return false;
  		}		
	}

	return true;
  }
  
  public function getNormalizedEmailAdresse(array $recipient){
  		assert(!!$recipient["email"]);
		
	  	$recip = "";
		if (! empty($recipient['givenname'])){
			$recip .= $recipient["givenname"] . " ";
		}
		if (! empty($recipient['name'])){
			$recip .=  $recipient["name"];
		}
		if ($recip){
			$recip .= " <" . $recipient["email"] . ">";
		} else {
			$recip = $recipient['email'];
		}
		return $recip;
  }

  /**
   * \brief Méthode d'encodage d'une chaîne en quoted-printable
   * \param $str chaîne : Chaîne à encoder
   * \param $add_mark booléen (optionnel) : Ajouter ou non le marqueur 'ISO-8859-1' (true par défaut)
   * \return La chaîne encodée en quoted-printable
   *
   */  
  public function quoted_printable_encode($str, $add_mark = true) {
	// pas de caractère à échapper
	if (! preg_match('/[^\x21-\x3C\x3E-\x7E\x09\x20]/', $str)) {
	  return $str;
	}

	// instead of replace_callback i used <b>e</b> modifier for regex rule, which works as eval php function
	$new = preg_replace('/[^\x21-\x3C\x3E-\x7E\x09\x20]/e', 'sprintf("=%02X",ord("$0"));', $str);

	// Ajout des marqueurs de début et de fin
	if ($add_mark) {
	  $new = preg_replace('/[^\s]*=[^\s]*/','=?ISO-8859-1?Q?$0?=', $new);
	}

	// Problème: coupe le début des chaines sans espace de plus de 73 caractères.
	preg_match_all('/.{1,73}( |$)/', $new, $aMatch);
	$new = implode("\r\n\t", $aMatch[0]);
	return $new;
  }
}