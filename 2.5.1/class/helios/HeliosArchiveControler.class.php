<?php
class HeliosArchiveControler {

	const PASSER_EN_ERREUR_APRES_NB_SECOND = 86400;

	private $sqlQuery;
	private $lastError;

	/** @var HeliosTransactionsSQL  */
	private $heliosTransactionsSQL;

	/** @var  PastellFactory */
	private $pastellFactory;

	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
		$this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$this->authoritySQL = new AuthoritySQL($this->sqlQuery);
		$this->setPastellFactory(new PastellFactory());
	}
	
	public function setPastellFactory(PastellFactory $pastellFactory){
		$this->pastellFactory = $pastellFactory;
	}

	public function getLastError(){
		return $this->lastError;
	}
	
	public function setArchiveEnAttenteEnvoiSEA($user_id,$id){
		try {
			$transactionsInfo = $this->heliosTransactionsSQL->getInfo($id);
			$user = new User($user_id);
			$user->init();
			$this->isAllowToSendArchive($user_id,$transactionsInfo);

			if (!in_array($transactionsInfo['last_status_id'], array(8,4, 6, 11,20))) {
				throw new Exception("Impossible d'archiver une transaction qui n'est pas en état « Information disponible », « acquitté » ou « refusé ».");
			}
			$this->authoritySQL->verifHasPastell($transactionsInfo['authority_id']);
		} catch (Exception $e){
			$this->lastError = $e->getMessage();
			return false;
		}
		$id = $this->heliosTransactionsSQL->updateStatus($id,HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,"En attente de l'envoi au SAE");
		return $id;
	}

	private function isAllowToSendArchive($user_id,$transactionsInfo){
		if (! $transactionsInfo){
			throw new Exception("Impossible de d'envoyer la transaction");
		}
		if ($transactionsInfo['user_id'] == $user_id){
			return true;
		}
		$transactionsInfo['authority_id'];
		$userSQL = new UserSQL($this->sqlQuery);
		$user_info = $userSQL->getInfo($user_id);

		if ($user_info['role'] == 'SADM'){
			return true;
		}

		if ($user_info['role'] != 'ADM'){
			throw new Exception("Accès interdit");
		}

		if ($user_info['authority_id'] == $transactionsInfo['authority_id']){
			return true;
		}

		throw new Exception("Accès interdit");
	}

	public function sendAllArchive(){
		echo "Début de l'envoie:\n";
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$info_list = $heliosTransactionSQL->getIdsByStatus(HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE);
		echo count($info_list)." transactions à envoyer...\n";
		foreach($info_list as $transaction_id){
			echo "Envoi de la transaction $transaction_id.\n";
			$this->sendArchive($transaction_id);
		}
		echo "Fin de l'envoie\n";
	}

	public function sendArchive($id){
		try {
			$this->sendArchiveThrow($id);
		} catch (Exception $e){
			echo "Impossible d'envoyer la transaction $id : " . $e->getMessage()."\n";
			$status_info = $this->heliosTransactionsSQL->getLastStatusInfo($id);
			$first_try = strtotime($status_info['date']);
			if (time() - $first_try > self::PASSER_EN_ERREUR_APRES_NB_SECOND){
				$this->heliosTransactionsSQL->updateStatus($id,
					HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
					"Le document n'a pas pu être envoyé au SAE");
				echo "Passage de la transaction en erreur !\n";
			}
		}
	}

	private function sendArchiveThrow($id){
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$transactionsInfo = $heliosTransactionsSQL->getInfo($id);

		$this->authoritySQL->verifHasPastell($transactionsInfo['authority_id']);

		$authoritySQL = new AuthoritySQL($this->sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($transactionsInfo['authority_id']);
		
		if (! $authorityInfo['pastell_url'] ){
			throw new Exception("La collectivité n'a pas de Pastell configuré");
		}

		$pastell = $this->pastellFactory->getNewInstance(
			$authorityInfo['pastell_url'],
			$authorityInfo['pastell_id_e'],
			$authorityInfo['pastell_login'],
			$authorityInfo['pastell_password']
		);


		$id_d = $pastell->createHelios($transactionsInfo);
		
		if (! $id_d){
			throw new Exception($pastell->getLastError());
		}
		
		$file_path = HELIOS_FILES_UPLOAD_ROOT . "/". $transactionsInfo['sha1'];
		
		$pastell->postFile($id_d,'fichier_pes',$file_path,$transactionsInfo['complete_name']);
				
		$pes_retour_path = HELIOS_RESPONSES_ROOT . "/".  $transactionsInfo['acquit_filename'];
		$pastell->postFile($id_d,'fichier_reponse',$pes_retour_path,$transactionsInfo['acquit_filename']);
		
		$result = $pastell->sendSAE($id_d);

		if (! $result){
			throw new Exception($pastell->getLastError());
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
		$xml_message = utf8_decode(strval($xml->{'ReplyCode'}) . " - " . strval($xml->{'Comment'}));

		/** @var HeliosTransactionsSQL $heliosTransactionsSQL */
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		
		
		//if ($nodeName == 'ArchiveTransferAcceptance'){
		if ($nodeName == 'ArchiveTransferAcceptance' || ($nodeName == 'ArchiveTransferReply' && (strval($xml->ReplyCode) == '000'))){
			$url = $info['data']['url_archive'];
			$msg = "La transaction {$transactionInfo['id']} a été acceptée par le SAE : \n$xml_message";
			$heliosTransactionsSQL->updateStatus($transactionInfo['id'],10,$msg);
			$heliosTransactionsSQL->setArchiveURL($transactionInfo['id'],$url);
		} else {
			$msg = "La transaction {$transactionInfo['id']} a été refusé par le SAE: \n$xml_message";
			$heliosTransactionsSQL->updateStatus($transactionInfo['id'],11,$msg);
		}
		
		echo "$msg\n";
		
		$pastell->delete($transactionInfo['sae_transfer_identifier']);
		echo "Document supprimé sur Pastell\n";
		
		return true;
	}
	
	
}