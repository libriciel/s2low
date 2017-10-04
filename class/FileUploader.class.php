<?php
class FileUploader {
	
	const MAX_LINE_LENGTH = 1024;
	
	private $forbidenExtension = array("asp","aspx","asax","asa","jsp","cer","cdx","asa","htr","php","php3","exe","cgi");	
	
	private $destinationDirectory; 	
	private $lastError;
	private $fileName;
	private $fileSize;		
	private $extension;
	
	private $fileHandler;
	
	
	public function setDestinationDirectory($directory){
	  	$this->destinationDirectory=$directory;	
	}
	
	public function disableForbidenExtension(){
		$this->forbidenExtension = array();
	}
	
	
	public function verifOKAll($formFileName){
		if (! isset($_FILES[$formFileName]) || ! $_FILES[$formFileName] ) {
			$this->lastError = "Il n'y a pas de fichier à charger sur le serveur";
	  	 	return false;
		}
		
		foreach( $_FILES[$formFileName]['error'] as  $i => $errorCode){
			if ( $errorCode !=  UPLOAD_ERR_OK  ){
			 	$this->setErrorMessage($errorCode);
			 	return false;
			}
	
			if($_FILES[$formFileName]['size'][$i]<=0) {
				$this->lastError = "Le fichier semble vide";
				return false;
			}
		}		
		return true;
	}
	
	public function verifOK($formFileName){
		if (! isset($_FILES[$formFileName]) || ! $_FILES[$formFileName] ) {
			$this->lastError = "Il n'y a pas de fichier à charger sur le serveur";
	  	 	return false;
		}
		
		$errorCode = $_FILES[$formFileName]['error'];
		if ( $errorCode !=  UPLOAD_ERR_OK  ){
		 	$this->setErrorMessage($errorCode);
		 	return false;
		}
		
		$this->fileName = $_FILES[$formFileName]['name'];		
		$this->fileSize=$_FILES[$formFileName]['size'];
		if($this->fileSize<=0) {
			$this->lastError = "Le fichier semble vide";
			return false;
		}		
		return true;
	}
	
	public function upload($formFileName) { 
		
		if ( ! $this->verifOK($formFileName)){
			return false;
		}
		
		$this->extension=strtolower(substr($this->fileName,strrpos($this->fileName,".")+1));
		 
		if(in_array($this->extension,$this->forbidenExtension)) {
			$this->lastError = "Le fichier ".$this->filename." contient une extension interdite";
			return false;
		}		
	  
		if(file_exists($this->destinationDirectory.$this->fileName)) {
	  		$this->lastError = "Le fichier ". $this->fileName." existe déjà sur le serveur";	   
	   		return false;
		}
  		if ($this->destinationDirectory){
		 	$res = move_uploaded_file($_FILES[$formFileName]['tmp_name'],$this->destinationDirectory.$this->fileName);
		 	if ($res == false){
		 		$this->lastError = "Impossible de recopier le fichier sur le serveur ";
		 		return false;
		 	}
  		} else {
  			$this->fileHandler = fopen($_FILES[$formFileName]['tmp_name'],"r");
  		}
	 	return true;	 	
	}	
	
	public function getLigne(){
		if (feof($this->fileHandler)){
			return false;
		}
		return fgets($this->fileHandler,self::MAX_LINE_LENGTH);
	}
	
	private function setErrorMessage($code){
		switch($code) {
			case  UPLOAD_ERR_INI_SIZE   :
				$message = "La taille du fichier excède la taille maximum (".ini_get('upload_max_filesize').")";
				break;
			case UPLOAD_ERR_FORM_SIZE : 
				$message = "Le fichier dépasse la taille limite autorisée par le formulaire";
				break;
			case UPLOAD_ERR_PARTIAL: 
				$message = "Le fichier n'a été que partiellement reçu";
				break;	
			case  UPLOAD_ERR_NO_FILE   :
				$message = "Aucun fichier n'a été présenté"; 
				break;
			case UPLOAD_ERR_NO_TMP_DIR: 
				$message = "Erreur de configuration : le répertoire temporaire n'existe pas";
				break;
			case UPLOAD_ERR_CANT_WRITE  : 
				$message = "Erreur de configuration : Impossible d'écrire dans le répertoire temporaire";
				break;
			case UPLOAD_ERR_EXTENSION  : 
				$message = "Une extension PHP empeche l'upload du fichier!";
				break;			
			default: 
				$message = "Erreur lors du chargement du fichier (code $code)";
				break;
		}
		$this->lastError = $message;
	}
	
	public function getFileName() {
		return $this->fileName;
	}
	
	public function getFileSize() {
		return $this->fileSize;
	}	
 
	function getLastError(){
		return $this->lastError;
	}
	
	public function getExtension() {
	  return $this->extension;
	}
}