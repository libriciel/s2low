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
	
	public function saveDIA($file_path,$id){
		copy($file_path,$this->dia_upload_path."/{$id}");
	}
	
	public function createAE($id){
		$xml = simplexml_load_file($this->getFilePath($id));
		$id_dia = strval($xml['Id']);
		$dia = $xml->children("http://xmlschema.ok-demat.com/DIA");

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		?>
<dia:accuseAEN 	Id="<?php echo $id_dia ?>" 
				xmlns:xad="http://uri.etsi.org/01903/v1.2.2#" 
				xmlns:n1="http://www.altova.com/samplexml/other-namespace" 
				xmlns:ds="http://www.w3.org/2000/09/xmldsig#" 
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
				xmlns:dia="http://xmlschema.ok-demat.com/DIA" 
				xmlns:diaco="http://xmlschema.ok-demat.com/DIA-CO" >
	<?php echo $dia->identification->asXML(); ?>
</dia:accuseAEN>
<?php 
		
		$ae_content= ob_get_clean();
		
		file_put_contents($this->dia_upload_path."/{$id}_ae", $ae_content);
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
	
	public function delete($id){
		@ unlink($this->dia_upload_path."/$id");
		@ unlink($this->dia_upload_path."/{$id}_ae");
		@ unlink($this->dia_upload_path."/{$id}_anp");
	}
	
}