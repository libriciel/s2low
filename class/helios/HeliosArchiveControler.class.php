<?php
class HeliosArchiveControler {

	const PASSER_EN_ERREUR_APRES_NB_SECOND = 86400;

	private $sqlQuery;

	/** @var HeliosTransactionsSQL  */
	private $heliosTransactionsSQL;

	/** @var  PastellWrapperFactory */
	private $pastellWrapperFactory;

	private $pesAllerRetriever;

	public function __construct(
	    SQLQuery $sqlQuery,
        PesAllerRetriever $pesAllerRetriever,
		PastellWrapperFactory $pastellWrapperFactory
    ){
		$this->sqlQuery = $sqlQuery;
		$this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$this->authoritySQL = new AuthoritySQL($this->sqlQuery);
		$this->pastellWrapperFactory = $pastellWrapperFactory;
		$this->pesAllerRetriever = $pesAllerRetriever;
	}

	public function sendAllArchive($authority_id = 0){
        $sigtermHandler = new SigTermHandler();
		echo "Début de l'envoie:\n";
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$info_list = $heliosTransactionSQL->getIdsByStatus(HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,$authority_id);
		echo count($info_list)." transactions à envoyer...\n";
		foreach($info_list as $transaction_id){
			echo "Envoi de la transaction $transaction_id.\n";
			$this->sendArchive($transaction_id);
            if ($sigtermHandler->isSigtermCalled()){
                break;
            }
		}
		echo "Fin de l'envoie\n";
	}

	public function sendArchive($id){
		try {
			$this->sendArchiveThrow($id);
		} catch (Exception $e){
			$message = "Le document n'a pas pu être envoyé sur Pastell : " . $e->getMessage();
			$this->heliosTransactionsSQL->updateStatus($id,
				HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
				$message);
			echo $message."\n";
		}
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws Exception
	 */
	private function sendArchiveThrow($id){
		$heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$transactionsInfo = $heliosTransactionsSQL->getInfo($id);

		$this->authoritySQL->verifHasPastell($transactionsInfo['authority_id']);

		$authoritySQL = new AuthoritySQL($this->sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($transactionsInfo['authority_id']);
		
		if (! $authorityInfo['pastell_url'] ){
			throw new Exception("La collectivité n'a pas de Pastell configuré");
		}

		$pastellPropertiesSQL = new PastellPropertiesSQL($this->sqlQuery);
		$pastellProperties = $pastellPropertiesSQL->getPastellProperties($transactionsInfo['authority_id']);

		$pastell = $this->pastellWrapperFactory->getNewInstance($pastellProperties);



		$id_d = $pastell->createHelios($transactionsInfo);
		
		if (! $id_d){
			throw new Exception($pastell->getLastError());
		}

		$file_path = $this->pesAllerRetriever->getPath($transactionsInfo['sha1']);

		$pastell->postFile($id_d,'fichier_pes',$file_path,$transactionsInfo['complete_name']);
				
		$pes_retour_path = HELIOS_RESPONSES_ROOT . "/".  $transactionsInfo['acquit_filename'];
		$pastell->postFile($id_d,'fichier_reponse',$pes_retour_path,$transactionsInfo['acquit_filename']);
		
		$result = $pastell->sendSAE($id_d,$pastellProperties->helios_action);

		if (! $result){
			throw new Exception($pastell->getLastError());
		}
		$heliosTransactionsSQL->updateStatus($id,9,"Envoie de la transaction $id à Pastell");
		$heliosTransactionsSQL->setSAETransferIdentifier($id,$id_d);
		return true;
	}


	public function verifArchive($transactionInfo){
		try {
			return $this->verifArchiveThrow($transactionInfo);
		} catch (Exception $e){
			echo "Problème lors de la vérificationd de l'archive : " . $e->getMessage();
			return false;
		}
	}

	/**
	 * @param $transactionInfo
	 * @return bool
	 * @throws Exception
	 */
	private function verifArchiveThrow($transactionInfo){
		echo "Transaction {$transactionInfo['id']} : ";
		
		$userSQL = new UserSQL($this->sqlQuery);
		$userInfo = $userSQL->getInfo($transactionInfo['user_id']);
		
		$authoritySQL = new AuthoritySQL($this->sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($userInfo['authority_id']);
				
		if (! $authorityInfo['pastell_url'] ){
			echo  "La collectivité n'a pas de Pastell configuré\n";
			return false;
		}

		$pastellPropertiesSQL = new PastellPropertiesSQL($this->sqlQuery);
		$pastellProperties = $pastellPropertiesSQL->getPastellProperties($userInfo['authority_id']);

		$pastellWrapper = $this->pastellWrapperFactory->getNewInstance($pastellProperties);

		$info = $pastellWrapper->getInfo($transactionInfo['sae_transfer_identifier']);
		if(!$info){
			echo $pastellWrapper->getLastError()."\n";
			return false;
		}
		try {
			$reply_sae = $pastellWrapper->getFile($transactionInfo['sae_transfer_identifier'], 'reply_sae');
		} catch (Exception $e){
			echo "Pas encore de réponse (".$e->getMessage().") \n";
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
		
		$pastellWrapper->delete($transactionInfo['sae_transfer_identifier']);
		echo "Document supprimé sur Pastell\n";
		
		return true;
	}
	
	
}