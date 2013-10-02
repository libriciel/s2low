<?php
class ActesArchiveControler {
	
	private $sqlQuery;
	private $lastError;
	
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getLastError(){
		return $this->lastError;
	}
	
	
	public function verifArchive($transactionInfo){
		
		
		echo "Transaction {$transactionInfo['unique_id']} : ";
		
		$authoritySQL = new AuthoritySQL($this->sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($transactionInfo['authority_id']);
		
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
		
		$actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
		
		
		if ($nodeName == 'ArchiveTransferAcceptance'){
			$url = $info['data']['url_archive'];
			$msg = "La transaction {$transactionInfo['id']} a été acceptée par le SAE : \n$xml_message";
			$actesTransactionsSQL->updateStatus($transactionInfo['id'],13,$msg,$reply_sae);
			$actesTransactionsSQL->setArchiveURL($transactionInfo['id'],$url);			
		} else {
			$msg = "La transaction {$transactionInfo['id']} a été refusé par le SAE: \n$xml_message";			
			$actesTransactionsSQL->updateStatus($transactionInfo['id'],14,$msg,$reply_sae);
		}
		
		echo "$msg\n";
		return true;
	}
	
	public function sendArchive($user_id,$id){
		
		$actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
		$transactionsInfo = $actesTransactionsSQL->getInfo($id);
		if ( ! $transactionsInfo || $transactionsInfo['user_id'] != $user_id){
			$this->lastError = "Accès refusé";
			return false;
		}
		
		if ($transactionsInfo['last_status_id'] != 4 && $transactionsInfo['type'] != 1) {
			$this->lastError = "Impossible d'archiver une transaction qui n'est pas en état « Acquittement reçu ».";
			return false;
		}
		
		
		$authoritySQL = new AuthoritySQL($this->sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($transactionsInfo['authority_id']);
		
		if (! $authorityInfo['pastell_url'] ){
			$this->lastError = "La collectivité n'a pas de Pastell configuré";
			return false;
		}
		
		$pastell = new Pastell($authorityInfo['pastell_url'],
						$authorityInfo['pastell_id_e'],
						$authorityInfo['pastell_login'],
						$authorityInfo['pastell_password']);

		$id_d = $pastell->createActes($transactionsInfo);
		
		if (! $id_d){
			$this->lastError = $pastell->getLastError();
			return false;
		}
		
		$actesFile = $actesTransactionsSQL->getAllFile($id);
		
		$actesEnvelopeSQL = new ActesEnvelopeSQL($this->sqlQuery);
		$actesEnvelopeInfo = $actesEnvelopeSQL->getInfo($transactionsInfo['envelope_id']);
		$enveloppe_path =  ACTES_FILES_UPLOAD_ROOT . "/" .  $actesEnvelopeInfo['file_path'];
		
		$tmpFolder = new TmpFolder();
		$tmp_folder = $tmpFolder->create();
		
		$tgzExtractor = new TGZExtractor($tmp_folder);
		$tgzExtractor->extract($enveloppe_path,$actesFile[1]['filename']);
		
		$acte_filename = $actesFile[0]['filename'];
		
		$pastell->postActes($id_d,$tmp_folder."/".$actesFile[1]['filename'],$actesFile[1]['posted_filename']);
		
		array_shift($actesFile);
		array_shift($actesFile);
		
		foreach($actesFile as $file){
			$tgzExtractor->extract($enveloppe_path,$file['filename']);
			$pastell->postAnnexe($id_d, $tmp_folder.'/'.$file['filename'], $file['posted_filename']);
		}
		
		$actesTransactionsStatusInfo = $actesTransactionsSQL->getStatusInfo($id,4);
		
		
		file_put_contents($tmp_folder."/AR-{$acte_filename}", $actesTransactionsStatusInfo['flux_retour']);
		$pastell->postARActes($id_d,$tmp_folder."/AR-{$acte_filename}");
		
		
		$orig_acte_transaction_id = $transactionsInfo['id'];
		
		file_put_contents($tmp_folder."/empty", "");
	
		$relatedTransaction = $actesTransactionsSQL->getRelatedTransaction($id);
		$echange_prefecture_type = array();
		$echange_prefecture = array();
		$echange_prefecture_ar = array();
		foreach($relatedTransaction as $transaction){
			
			$actesEnvelopeInfo = $actesEnvelopeSQL->getInfo($transaction['envelope_id']);
			$actesFile = $actesTransactionsSQL->getAllFile($transaction['id']);
			$file_to_send =  ACTES_FILES_UPLOAD_ROOT . "/" .  $actesEnvelopeInfo['file_path'];
			
			if ($transaction['related_transaction_id'] == $orig_acte_transaction_id){
				//Transaction aller
				$echange_prefecture_type[] = $transaction['type'].'A';
				$filename = $actesFile[0]['filename'];
				$posted_filename = $actesFile[0]['posted_filename'];
				array_shift ($actesFile);
				$status_info =  $actesTransactionsSQL->getStatusInfo($transaction['id'],8);
			} else {
				//Transaction retour
				$echange_prefecture_type[] = $transaction['type'].'R';
				
				$filename = $actesFile[1]['filename'];
				$posted_filename = $actesFile[1]['posted_filename'];
				array_shift ($actesFile);
				array_shift ($actesFile);
				$status_info =  $actesTransactionsSQL->getStatusInfo($transaction['id'],11);	
			}
			$tgzExtractor->extract($file_to_send,$filename);
			$echange_prefecture[] = array($tmp_folder."/".$filename,$posted_filename);
			
			if ($status_info && $status_info['flux_retour']){
				$ar_name = "AR-".$status_info['transaction_id'].".xml";
				file_put_contents($tmp_folder."/$ar_name", $status_info['flux_retour']);
				$echange_prefecture_ar[] = array($tmp_folder."/$ar_name",$ar_name);
			} else {
				$echange_prefecture_ar[] = array($tmp_folder."/empty",'empty');
			}
			
			foreach($actesFile as $annexe){
				$tgzExtractor->extract($file_to_send."/".$annexe['filename'],$annexe['filename']);
				$echange_prefecture_type[] = $transaction['type'].'RB';
				$echange_prefecture[] = array($file_to_send."/".$annexe['filename'],$annexe['posted_filename']);
				$echange_prefecture_ar[] = array($tmp_folder."/empty",'empty');
			}
		}
		
		$pastell->postRelatedTransaction($id_d,$echange_prefecture_type,$echange_prefecture,$echange_prefecture_ar);
		$tmpFolder->delete($tmp_folder);
		
		
		$result = $pastell->sendSAE($id_d);

		if (! $result){
			$this->lastError = $pastell->getLastError();
			return false;
		}
		$actesTransactionsSQL->updateStatus($id,12,"Envoie de la transaction $id à Pastell");
		$actesTransactionsSQL->setSAETransferIdentifier($id,$id_d);
		return true;
	}
	
}