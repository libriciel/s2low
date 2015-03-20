<?php
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesTransaction.class.php');

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
		
		$pastell->delete($transactionInfo['sae_transfer_identifier']);
		echo "Document supprimé sur Pastell\n";
		
		return true;
	}
	
	public function sendArchive($user_id,$id){
		
		$actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
		$transactionsInfo = $actesTransactionsSQL->getInfo($id);
		$user = new User($user_id);
		$user->init();
		if ( ! $transactionsInfo || ($transactionsInfo['user_id'] != $user_id && !$user->isAdmin())){
			$this->lastError = "Accès refusé (seul le créateur de l'Acte peut l'archiver)";
			return false;
		}
		
		if (! in_array($transactionsInfo['last_status_id'],array(4,5))  && $transactionsInfo['type'] != 1) {
			$this->lastError = "Impossible d'archiver une transaction qui n'est pas en état « Acquittement reçu » ou « Validé ».";
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
		
		if ($actesFile[1]['signature']){
			$signature_file_path = $tmp_folder."/signature.pk7";
			file_put_contents($signature_file_path, $actesFile[1]['signature']);
			$pastell->postSignature($id_d, $signature_file_path);
		}
		
		
		$pdftampone = $tmp_folder."/".$actesFile[1]['filename'];
		$path_parts = pathinfo($pdftampone);
		if ($path_parts['extension'] == 'pdf' || $path_parts['extension'] == 'PDF'){
			$datetampon = $actesTransactionsSQL->getDateTampon($transactionsInfo['id']);
			$pdftampone = $this->tamponerActe($tmp_folder,$actesFile[1]['filename'],$datetampon);
		}
		$pastell->postFile($id_d,"acte_tamponne",$pdftampone,"acte_tampone.".$path_parts['extension']);

		$datepostage = $actesTransactionsStatusInfo = $actesTransactionsSQL->getStatusInfo($transactionsInfo['id'],1);
		$pastell->setDatePostage($id_d,date("d/m/Y",time($datepostage['date'])));

		$trans = new ActesTransaction();
		$trans->setId($id);
		if ( ! $trans->init()) {
			$_SESSION["error"] = "Erreur d'initialisation de la transaction.";
			header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
			exit ();
		}
		$owner = new User($transactionsInfo['user_id']);
		$owner->init();
		
		//passer les paramètre
		$pdf=new ActesPdf($trans,$owner);
		
		//construire le fichier pdf.
		$pdf->create_pdf();
		$pdf->output($tmp_folder."/bordereau_acquit","F");
		$pastell->postFile($id_d,"bordereau",$tmp_folder."/bordereau_acquit.pdf","bordereau_acquittement.pdf");
		
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
	
	public function tamponerActe($tmpfolder,$fileorig,$transactionInfo){
		set_include_path(SITEROOT."/ext/" . PATH_SEPARATOR .   get_include_path());
		require_once(SITEROOT."/class/TamponPDF.class.php");
		$pdftkise=$tmpfolder."/tampon_".$fileorig;
		$pdftkise = $this->modificationPDF($tmpfolder."/".$fileorig, $pdftkise);
		try{
		        $pdf = Zend_Pdf::load($pdftkise);
		        $tampon = new TamponPDF($pdf);
		        $tampon->setText(array("Envoyé en préfecture le ".date("d/m/Y",strtotime($transactionInfo['submission_date'])),
				                            "Reçu en préfecture le ".date("d/m/Y",strtotime($transactionInfo['date'])),
				                            "Affiché le " ,
		        							"ID : ".$transactionInfo['unique_id']));
		        $tampon->setNameFile("tampon_".$fileorig);
		        file_put_contents($pdftkise,$tampon->getFileAsString());
		} catch (Exception $e){
		}
		return $pdftkise;
	}
				
	public function modificationPDF($pathpdforig, $pathpdfout){
		$cmdpdftk='timeout 10 pdftk '. $pathpdforig." stamp ".SITEROOT."/data-exemple/vide.pdf output ".$pathpdfout;
		Trace::wrap_exec($cmdpdftk, $status, $ret);
		if ($status === false || $ret != 0) {
	        $cmdpdftk='timeout 10 pdfsam-console -f '. $pathpdforig ." -o ". $pathpdfout ." concat";
	        Trace::wrap_exec($cmdpdftk, $status, $ret);
	        if ($status === false || $ret != 0){
				$pathpdfout=$pathpdforig;
	        }
       }//fin if
       return $pathpdfout;
	}
}