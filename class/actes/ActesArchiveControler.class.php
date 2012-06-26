<?php
class ActesArchiveControler {
	
	public function getBordereau($id){
		
		global $connexion;
		global $sqlQuery;
		
		$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
		$transactionsInfo = $actesTransactionsSQL->getInfo($id);
		$latest_date = $actesTransactionsSQL->getLatestDate($id);
		if ( ! $transactionsInfo || $transactionsInfo['user_id'] != $connexion->getId()){
			sortir("Accès refusé");
		}
		
		if ($transactionsInfo['last_status_id'] != 4 && $transactionsInfo['type'] != 1) {
			$_SESSION['error'] = "Impossible d'archiver une transaction qui n'est pas en état « Acquittement reçu ».";
			header("Location: actes_transac_show.php?id=$id");
			exit;
		}
		
		$numero_transfert = $actesTransactionsSQL->getNextNumeroTransfert();
		
		$authoritySQL = new AuthoritySQL($sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($transactionsInfo['authority_id']);
		
		if (! $authorityInfo['sae_numero_aggrement'] ){
			$_SESSION['error']  = "La collectivité ne présente pas de numéro d'aggrément";
			header("Location: actes_transac_show.php?id=$id");
			exit;
		}
		
	
		
		
		$actesEnvelopeSQL = new ActesEnvelopeSQL($sqlQuery);
		$actesEnvelopeInfo = $actesEnvelopeSQL->getInfo($transactionsInfo['envelope_id']);
		
		$actesTransactionsStatusInfo = $actesTransactionsSQL->getStatusInfo($id,4);
		$actesFile = $actesTransactionsSQL->getAllFile($id);
		
		
		$file_to_send =  ACTES_FILES_UPLOAD_ROOT . "/" .  $actesEnvelopeInfo['file_path'];
		
		$tgzExtractor = new TGZExtractor('/tmp');
		$tgzExtractor->extract($file_to_send,$actesFile[1]['filename']);
		
		$relatedTransaction = $actesTransactionsSQL->getRelatedTransaction($id);
		
		
		$actesArchivesSEDA = new ActesArchiveSEDA("/tmp/");
		$actesArchivesSEDA->setAuthorityInfo($authorityInfo);
		$actesArchivesSEDA->setActesFileName($actesFile[1]['filename'],$actesFile[1]['signature']);
		$actesArchivesSEDA->setTransactionStatusInfo($actesTransactionsStatusInfo);
		$actesArchivesSEDA->setNumeroTransfert($numero_transfert);
		$actesArchivesSEDA->setLatestDate($latest_date);
		
		
		array_shift ($actesFile);
		array_shift ($actesFile);
		
		foreach($actesFile as $annexe){
			$tgzExtractor->extract($file_to_send,$annexe['filename']);
			$actesArchivesSEDA->addAnnexe($annexe['filename'],$annexe['filetype'],$annexe['signature']);
		}
		
		foreach($relatedTransaction as $transaction){
			$actesEnvelopeInfo = $actesEnvelopeSQL->getInfo($transaction['envelope_id']);
			$actesFile = $actesTransactionsSQL->getAllFile($transaction['id']);
			$file_to_send =  ACTES_FILES_UPLOAD_ROOT . "/" .  $actesEnvelopeInfo['file_path'];
			
			if ($transaction['type_reponse']){
				$filename = $actesFile[1]['filename'];
				array_shift ($actesFile);
				array_shift ($actesFile);	
			} else {
				$filename = $actesFile[0]['filename'];
				array_shift ($actesFile);
			}
			$tgzExtractor->extract($file_to_send,$filename);
			$af = array(array($filename,"application/pdf"));
			
			foreach($actesFile as $annexe){
				$tgzExtractor->extract($file_to_send,$annexe['filename']);
				$af[] = array($annexe['filename'],$annexe['filetype']);
			}
			$actesArchivesSEDA->addRelatedTransaction($transaction,$af);
		}
		
		$archive_path = $actesArchivesSEDA->getArchive();
		if (! $archive_path){
			$_SESSION['error'] = $actesArchivesSEDA->getLastError();
			header("Location: actes_transac_show.php?id=$id");
			exit;
		}
		

		
		$bordereau = $actesArchivesSEDA->getBordereau($transactionsInfo);
		return array($actesTransactionsSQL,$transactionsInfo,$bordereau,$archive_path);
		
	}
	
	
	
	
}