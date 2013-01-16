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
		return $versionning->getAllInfo();
	}
	
	public function listDia($status,$page_number,$taille_page){
		$this->transactionDIA->setStatus($status);
		$this->transactionDIA->setPageNumber($page_number, $taille_page);
		foreach($this->transactionDIA->getAll() as $transaction){
			$result[] = 
				array(
					'id' => $transaction['transaction_id'],
					'date-reception' => $transaction['submission_date'],
					'filename' => $transaction['filename'],
					'filesize' => $transaction['filesize'],
					'status' => $transaction['last_status_id'],
					'status_txt' => $transaction['current_status_name'],
				);
		}
		return $result;
	}


	public function getDIA($id){
		$info = $this->transactionDIA->getInfo($id);
		if (!$info){
			return $this->getError("identifiant de la DIA inconnu");
		}
		$result['filename'] = $info['filename'];
		$result['file_content']  = file_get_contents($this->fileDIA->getFilePath($id));
		if ($info['last_status_id'] == TransactionDIA::RECU) {
			$this->transactionDIA->updateStatus($id,TransactionDIA::RECUPERE,"DIA récupéré via SOAP");
		}
		return $result;
		
	}
	
	public function setAE($id,$filename,$filecontent){
		$info = $this->transactionDIA->getInfo($id);
		if (!$info){
			return $this->getError("identifiant de la DIA inconnu");
		}
		if (! in_array($info['last_status_id'],array(1,2))){
			return $this->getError("AE déjà envoyé ! " );
		}
		$this->fileDIA->saveAE($id,$filecontent);
		$this->transactionDIA->addAE($id,$filename);
		return "ok";
	}
	

	
}