<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématérialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/



/**
 * \class MailUtil.class.php
 * \brief fonction commun pour envoiyer les email.
 * 
 * Cette classe fournit des méthodes d'envois email de module mail.
 *
 * \author TH ,JMontiel
 * \date :23-04-2008
 * 
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT."/class/pearMail.class.php");


class MailUtil {
 	
	public $errorMsg;
	private $trace;
	private $subject;
	private $from;
	
	public function __construct(){
		$this->trace = Trace::getInstance();
		$this->setSubjet(MAIL_MESSAGE);
		$this->setFrom(MAIL_TEDETIS_FROM);	
	}
	
	/**
	 * \brief	Permet de vérifier qu'un repertoire ne contient pas
	 * 			de fichier infesté par un virus.
	 * 
	 * \param	$path string le chemin du repertoire à analyser
	 * 
	 * \return 	true : le répertoire ne contient pas de fichier infecter,
	 * 		 	false sinon (fichier infecté, impossible d'appeller la commande
	 * 		 	$this->errorMsg contient le message d'erreur
	 * 
	 */
 	public function checkSanity($path){
 		
 		Trace::wrap_exec(ANTIVIRUS_COMMAND . " " . $path, $output, $ret);
		
		switch ($ret) {
			
	  		case 0:
				return true;
				break;
				
	  		case 1:
				$this->errorMsg = "L'archive est infectée par un virus. Retour de l'antivirus&nbsp;:<br />\n";
				// Format de ligne : /Nom/de/fichier: Nom virus
				foreach ($output as $line) {
			  		if (preg_match('/^\/.*: .* FOUND$/', $line)) {
						$line = explode(":", $line);
						$this->errorMsg .= basename($line[0]) . " : " . $line[1] . "<br />\n";
		  			}
				}
				return false;
				break;
				
	  		default:
				$this->errorMsg = "Erreur " . $ret . " lors du scan antivirus de l'archive.";
				return false;
	  		}
 	}
 	
 	/**
 	 * \brief extrait un tableau de fichier à partir d'un repertoire
 	 * \param $root le chemin racine
 	 * \return array le tableau des fichiers (n'inclue pas les repertoire)
 	 */
	public function path2array( $root) {
    	$result = array();
    	$dh = @opendir( $root );
    	if( false === $dh ) {
        	return $result;
    	}
    
    	while( $file = readdir( $dh )) {
        	if( "." == $file || ".." == $file ){
            	continue;
        	}
        	
        	if( is_dir( $root."/".$file )) {
            	$result += $this->path2array( $root."/".$file);
        	} else {
        		array_push($result,$root."/".$file);
        	}
    	}
    	closedir( $dh );
    	return $result;
	}
 	
 	/**
 	 * \brief 	zip les fichiers dont le chemin est dans array_path et 
 	 * 			met le résultat dans $file
 	 * \param	array_file array tableau des fichiers à zipper
 	 * \param	$file string nom du fichier de sortie
 	 * \return	true si ok false sinon. $this->errorMsg contient le message d'erreur
 	 */
 	public function zip($array_file, $file){
		$zip = new ZipArchive();
		$res = $zip->open($file, ZipArchive::CREATE);
		
		if (! $res){
			$this->errorMsg = "Impossible de créer l'archive zip $file : erreur ".$res;
			$this->trace->log($this->errorMsg,Trace::$TRACE_ERROR);
			return false;
		} 
		
		foreach ($array_file as $fichier) {
			if (!$zip->addFile($fichier, basename($fichier))) {
				$this->errorMsg = "Impossible de mettre le fichier $fichier dans l'archive $file ";
			 	$this->trace->log($this->errorMsg,Trace::$TRACE_ERROR);
			 	return false;
			}
		}
		$zip->close();
		return true;
 	}

	/**
 	 * \brief 	Signe le fichier $file et met le résultat dans $file_signature
 	 * 			Cette fonction calcule la signature pkcs7 et la détache
 	 * 			du message smime
 	 * \param	$file le fichier à signer
 	 * \param	$file_signature le fichier signature pkcs7
 	 * \return	true si ok false sinon. $this->errorMsg contient le message d'erreur
 	 */ 	 	
 	public function sign($file, $file_signature){
 		$parapheur = new Parapheur(file_get_contents($file));
 		$parapheur->setSignerParameter(TEDETIS_CERT_PATH,TEDETIS_KEY_PATH,TEDETIS_KEY_PASS);
 		$signature = $parapheur->getSignature();
		if (! $signature ){
			$this->errorMsg = $parapheur->getLastError();
			return false;
		}
		file_put_contents($file_signature);
 		return true;
 	}
 	
 	/**
 	 * \brief 	Signe un fichier $file en PKCS7
 	 * \param	$file le fichier à signer
 	 * \param	$file_signature le fichier résultat
 	 * \param   $headers Les entetes supplémentaire
 	 * \return	true si ok false sinon. $this->errorMsg contient le message d'erreur
 	 */ 	 
 	private function pkcs7_sign($file,$file_signature,$headers){
		$result = openssl_pkcs7_sign($file, 
 						$file_signature,
 						"file://".TEDETIS_CERT_PATH,
 						array("file://".TEDETIS_KEY_PATH, TEDETIS_KEY_PASS),
 						$headers,
 						PKCS7_DETACHED
 					);
    	if (! $result){
    		$this->errorMsg = "Erreur openssl : ".openssl_error_string();
    		$this->trace->log($this->errorMsg,Trace::$TRACE_ERROR);
    		return false;
    	}
    	return true;
 	}
 	
 	public function setSubjet($subject){
 		$this->subject = $subject; 		
 	}
 	
 	public function setFrom($from){
 		if ($from){
 			$this->from = $from;
 		}
 	}
 	
 	/**
   * \brief   envoyer un mail avec des pièces joindures.
   * \param   objet du class : mail_message_emis
   * \param                    mail_transaction
   * \param                    mail_include_file
   * \param   
   * \return  true si ok false sinon.
   */    
	public function sendMail($MailMessageEmis,$mailTransaction,$MailIncludeFiles,$send_password = false)
	{
	  	 $from = $this->from;
	  	 $from = "-f{$from}";  
	  	 
		
		$text=MAIL_TEXT;
		if ($mailTransaction->getPassword() ) {
			$text.="\n\n";
			if ($send_password) {
				$text.="Le mot de passe du document est : ".$mailTransaction->getPassword()."\n";
			} else {
				$text.="Le document est protégé par un mot de passe";
			}
		}
		

		$html='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 TRANSITIONAL//EN">
<html>
  <body bgcolor="#ffffff" text="#000000">
';
		$html.= "<p>".nl2br($text)."</p>";

		$hdrs = array(
              	'From'    => $this->from,
              	'Subject' => $this->subject,
				'Reply-To' => $this->from,
				'Return-path' => $this->from
              );
             
             
		    	
	  	foreach ($MailMessageEmis as $MailEmis )
   		{
	    	if (defined('MAIL_DEBUG'))
	    	{
				echo "<p>mail file number =".$MailFileNumber;"</p>";	
				echo "$MailEmis->getEmail()";
	    	}	
	    	$htmlpart='';	
			$htmlpart.='
<a href="'.WEBSITE.'/modules/mail/?command=show&mail_emis_id='.$MailEmis->getId().'" >Confirmer la reception et lire le courrier en cliquant sur ce lien</a><br>
<p>Note de securite :</p>
<p>Tous les documents mis a disposition par ce lien ont ete testes par l\'anti-virus CLAMAV -</p>
';

	    	$htmlpart.='';
			
			$textpart=WEBSITE."/modules/mail/index.php?command=show&mail_emis_id=".$MailEmis->getId();
			$textpart.="\n -Note de sécurité :
Tous les documents mis à disposition par ce lien ont été testés par l'anti-virus CLAMAV - \n";
			
			$crlf="\n";
			$mime = new Mail_mime($crlf);
			$mime->setTXTBody($text.$textpart);
			$mime->setHTMLBody($html.$htmlpart);
			
			//do not ever try to call these lines in reverse order
			$body = $mime->get();
			$hdrs = $mime->headers($hdrs);	
			$hdrs['Subject']= "=?ISO-8859-1?Q?".strtr(imap_8bit($this->subject),' ','_')."?=";
		
			
			$mail =new pearMail();
			$mail->sep = $crlf;
  			if (!$mail->send($MailEmis->getEmail(), $hdrs, $body,$from)) {
	        		return false;
  			}
    	}	
     return true;
	}

  public function GetMailMessage()
  {
	$mbox = imap_open("{".IMAP_SERVER."/novalidate-cert}",IMAP_LOGIN, IMAP_PASS);
	//echo imap_last_error();
	$headers = imap_headers($mbox);
	
	if ($headers == false) {
	    return false;
	}
	$mailEmis=array("mail_id"=>array(),"mail_emis_id"=>array());
  	$mail='mail_id=';
	$emis="mail_emis_id=";
	$mailLength=strlen($mail);
	$emisLength=strlen($emis);
	for($x=1; $x <=count($headers); $x++) {
		$mailBody=imap_body($mbox,$x);
		$mailId=strstr($mailBody,$mail);
		if ($mailId !=null)
		{
			$mailEmisId=strstr($mailId,$emis);
			if ($mailEmisId!=null)
			{		
				$mailEmisId=substr($mailEmisId,$emisLength,strlen($mailEmisId)-strlen(strstr($mailEmisId,"-"))-$emisLength);
				$mailId=substr($mailId,$mailLength,strlen($mailId)-strlen(strstr($mailId,"&"))-$mailLength);
				$mailEmis["mail_id"][]=$mailId;
				$mailEmis["mail_emis_id"][]=$mailEmisId;
				$mailEmis["body"][]=$mailBody;
				if (defined('MAIL_DEBUG'))
		    	{
					echo "<p>mail_id = $mailId     mail_emis_id= $mailEmisId </p>";
				}
			}
		}	
		imap_delete($mbox,$x);
	}
	imap_expunge($mbox);
	imap_close($mbox);
	return $mailEmis;
   }

}
