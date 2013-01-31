<?php 

class DIAAction {
	
	private $transactionDIA;
	private $fileDIA;
	
	public function __construct(TransactionDIA $transactionDIA, FileDIA $fileDIA ){
		$this->transactionDIA = $transactionDIA;
		$this->fileDIA = $fileDIA;
	}
	
	//SOAP passe la valeur NULL si on ne précise pas de valeur 
	private function setDefault(& $variable,$default){
		if (! $variable){
			$variable = $default;
		}
	}
	
	private function getError($Errormessage){
		$result['status'] = 'error';
		$result['error-message'] = $Errormessage;;
		return $result;
	}
	
	public function version(){
		$versionning = VersionningFactory::getInstance();
		$info = $versionning->getAllInfo();
		$info['version_complete'] = $info['version-complete'];
		return $info;
	}
	
	public function listDia($status){
		$this->transactionDIA->setStatus($status);
		return $this->transactionDIA->getAllId();
	}


	public function getDIA($id){
		$info = $this->transactionDIA->getInfo($id);
		if (!$info){
			throw new Exception("Identifiant de la DIA inconnu");
		}
		
		$result['date_reception'] = $info['submission_date'];
		$result['status'] = $info['last_status_id'] ;
		$result['status_txt'] = TransactionDIA::getStatusName($info['last_status_id']);
		$result['filename'] = $info['filename'];
		$result['filesize'] = $info['file_size'];
		$result['file_content']  = file_get_contents($this->fileDIA->getFilePath($id));
		
		if ($info['last_status_id'] == TransactionDIA::AE_ENVOYE) {
			$this->transactionDIA->updateStatus($id,TransactionDIA::RECUPERE,"DIA récupéré via SOAP");
		}
		return $result;
		
	}
	
	public function setAccuseNonPreemption($id,$filename,$filecontent){
		$this->reponsePossible($id);
		$this->fileDIA->saveANP($id,$filecontent);
		$this->transactionDIA->addAccuseNonPreemption($id,utf8_decode($filename));
		return array("message" => "Accusé de non péremption sauvegardé sur S²low");
	}
	
	public function setErreur($id,$erreur_message){
		$this->reponsePossible($id);
		$this->transactionDIA->updateStatus($id,TransactionDIA::ERREUR,utf8_decode($erreur_message));
		return array("message" => "Erreur enregistrée");
	}
	
	private function reponsePossible($id){
		$info = $this->transactionDIA->getInfo($id);
		if (!$info){
			throw new Exception("identifiant de la DIA inconnu");
		}
		if (! in_array($info['last_status_id'],array(1,2))){
			throw new Exception("AE ou erreur déjà envoyé ! " );
		}
	}

	
}