<?php 

class FileDIA {
	
	private $dia_upload_path;
	private $lastError;
	
	public function __construct($dia_upload_path){
		$this->dia_upload_path = $dia_upload_path;
	}
	
	public function getLastError(){
		return $this->lastError;
	}
	
	public function saveFromUpload($form_name){
		if ($_FILES[$form_name]['error'] != UPLOAD_ERR_OK){
			$this->lastError = "Erreur lors de la récupération du fichier : " . $_FILES[$form_name]['error'];
			return false;
		}
		$tmp_name = md5(mt_rand());
		if (!is_writable($this->dia_upload_path)){
			$this->lastError = "Impossible d'écrire dans le répertoire " . $this->dia_upload_path;
			return false;
		}
		move_uploaded_file($_FILES[$form_name]['tmp_name'], $this->dia_upload_path."/$tmp_name");
		return $tmp_name;
	}

	public function rename($oldname,$newname){
		rename($this->dia_upload_path."/$oldname", $this->dia_upload_path."/$newname");
	}
	
	public function getFilePath($id){
		return $this->dia_upload_path."/$id";
	}
	
	public function send($id,$filename){
		$this->header($filename);
		readfile($this->getFilePath($id));
	}
	
	public function setANP($tmp_name,$id){
		 $this->rename($tmp_name,$id."_anp");
	}
	
	public function saveANP($id,$filecontent){
		file_put_contents($this->dia_upload_path."/{$id}_anp", $filecontent);
	}
	
	public function saveAE($id,$filecontent){
		file_put_contents($this->dia_upload_path."/{$id}_ae", $filecontent);
	}
	
	public function sendANP($id,$filename){
		$this->header($filename);
		readfile($this->getFilePath($id."_anp"));
	}
	
	public function sendAE($id,$filename){
		$this->header($filename);
		readfile($this->getFilePath($id."_ae"));
	}
	
	private function header($filename){
		header('Content-Type: text/xml');
		header('Content-disposition: filename="'.$filename.'"');
	}
	
}