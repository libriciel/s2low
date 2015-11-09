<?php
class HeliosArchiveControler {
	
	private $sqlQuery;
	private $lastError;
	
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getLastError(){
		return $this->lastError;
	}
	

	private function isAllowToSendArchive($user_id,$transactionsInfo){
		if (! $transactionsInfo){
			return false;
		}
		if ($transactionsInfo['user_id'] == $user_id){
			return true;
		}
		$transactionsInfo['authority_id'];
		$userSQL = new UserSQL($this->sqlQuery);
		$user_info = $userSQL->getInfo($user_id);
		if ($user_info['role'] != 'ADM'){
			return false;
		}

		if ($user_info['authority_id'] == $transactionsInfo['authority_id']){
			return true;
		}

		return false;
	}

	public function sendArchive($user_id,$id){
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$transactionsInfo = $heliosTransactionsSQL->getInfo($id);

		if (! $this->isAllowToSendArchive($user_id,$transactionsInfo)){
			$this->lastError = "Accès refusé";
			return false;
		}
		
		$last_status_id = $heliosTransactionsSQL->getLatestStatusId($id);
		if (! in_array($last_status_id,array(8,4,6)) ) {
			$this->lastError = "Impossible d'archiver une transaction qui n'est pas en état « Information disponible », « acquitté » ou « refusé ».";
			return false;
		}
		
		$userSQL = new UserSQL($this->sqlQuery);
		$userInfo = $userSQL->getInfo($user_id);
		
		$authoritySQL = new AuthoritySQL($this->sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($userInfo['authority_id']);
		
		if (! $authorityInfo['pastell_url'] ){
			$this->lastError = "La collectivité n'a pas de Pastell configuré";
			return false;
		}
		
		$pastell = new Pastell($authorityInfo['pastell_url'],
						$authorityInfo['pastell_id_e'],
						$authorityInfo['pastell_login'],
						$authorityInfo['pastell_password']);

		$id_d = $pastell->createHelios($transactionsInfo);
		
		if (! $id_d){
			$this->lastError = $pastell->getLastError();
			return false;
		}
		
		$file_path = HELIOS_FILES_UPLOAD_ROOT . "/". $transactionsInfo['sha1'];
		
		$pastell->postFile($id_d,'fichier_pes',$file_path,$transactionsInfo['complete_name']);
				
		$pes_retour_path = HELIOS_RESPONSES_ROOT . "/".  $transactionsInfo['acquit_filename'];
		$pastell->postFile($id_d,'fichier_reponse',$pes_retour_path,$transactionsInfo['acquit_filename']);
		
		$result = $pastell->sendSAE($id_d);

		if (! $result){
			$this->lastError = $pastell->getLastError();
			return false;
		}
		$heliosTransactionsSQL->updateStatus($id,9,"Envoie de la transaction $id à Pastell");
		$heliosTransactionsSQL->setSAETransferIdentifier($id,$id_d);
		return true;
	}
	
	public function verifArchive($transactionInfo){
		
		echo "Transaction {$transactionInfo['id']} : ";
		
		$userSQL = new UserSQL($this->sqlQuery);
		$userInfo = $userSQL->getInfo($transactionInfo['user_id']);
		
		$authoritySQL = new AuthoritySQL($this->sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($userInfo['authority_id']);
				
		if (! $authorityInfo['pastell_url'] ){
			echo  "La collectivité n'a pas de Pastell configuré\n";
			return false;
		}
		
		$pastell = new Pastell($authorityInfo['pastell_url'],
						$authorityInfo['pastell_id_e'],
						$authorityInfo['pastell_login'],
						$authorityInfo['pastell_password']);

		$info = $pastell->getInfo($transactionInfo['sae_transfer_identifier']);
		if(!$info){
			echo $pastell->getLastError()."\n";
			return false;
		}
		$reply_sae = $pastell->getFile($transactionInfo['sae_transfer_identifier'],'reply_sae');
		if (! $reply_sae){
			echo "Pas encore de réponse (".$pastell->getLastError().") \n";
			return false;
		}
		
		@ $xml = simplexml_load_string($reply_sae);
		
		if (! $xml){
			echo "Impossible de lire le fichier reply.xml : $reply_sae\n";
			return false;
		}
		
		
		$nodeName = strval($xml->getName());
		$xml_message = utf8_decode(strval($xml->ReplyCode) . " - " . strval($xml->Comment));
		
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		
		
		if ($nodeName == 'ArchiveTransferAcceptance'){
			$url = $info['data']['url_archive'];
			$msg = "La transaction {$transactionInfo['id']} a été acceptée par le SAE : \n$xml_message";
			$heliosTransactionsSQL->updateStatus($transactionInfo['id'],10,$msg,$reply_sae);
			$heliosTransactionsSQL->setArchiveURL($transactionInfo['id'],$url);			
		} else {
			$msg = "La transaction {$transactionInfo['id']} a été refusé par le SAE: \n$xml_message";			
			$heliosTransactionsSQL->updateStatus($transactionInfo['id'],11,$msg,$reply_sae);
		}
		
		echo "$msg\n";
		
		$pastell->delete($transactionInfo['sae_transfer_identifier']);
		echo "Document supprimé sur Pastell\n";
		
		return true;
	}
	
	
}