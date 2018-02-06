<?php
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesTransaction.class.php');

/* Archive au sens SEDA et pas au sens Actes ... */

class ActesArchiveControler {

	/* Nombre de seconde avant de considérer l'envoi comme une erreur */
	const PASSER_EN_ERREUR_APRES_NB_SECOND = 86400;

	private $sqlQuery;
	private $lastError;

	/** @var  PastellFactory */
	private $pastellFactory;

	/** @var  ActesTransactionsSQL */
	private $actesTransactionsSQL;

	/** @var AuthoritySQL  */
	private $authoritySQL;

	private $actesRetriever;

	public function __construct(
	    SQLQuery $sqlQuery,
        ActesRetriever $actesRetriever
    ){
		$this->sqlQuery = $sqlQuery;
		$this->setPastellFactory(new PastellFactory());
		$this->actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
		$this->authoritySQL = new AuthoritySQL($this->sqlQuery);
		$this->actesRetriever = $actesRetriever;
	}

	public function setPastellFactory(PastellFactory $pastellFactory){
		$this->pastellFactory = $pastellFactory;
	}

	public function getLastError(){
		return $this->lastError;
	}

	public function setArchiveEnAttenteEnvoiSEA($user_id,$id){
		try {
			$transactionsInfo = $this->actesTransactionsSQL->getInfo($id);
			$user = new User($user_id);
			$user->init();
			if ( ! $transactionsInfo || ($transactionsInfo['user_id'] != $user_id && !$user->isAdmin())){
				throw new Exception("Accès refusé (seul le créateur de l'Acte peut l'archiver)");
			}
			if (!in_array($transactionsInfo['last_status_id'], array(4, 5, 14,20)) && $transactionsInfo['type'] != 1) {
				throw new Exception("Impossible d'archiver une transaction qui n'est pas en état « Acquittement reçu » ou « Validé ».");
			}
			$this->authoritySQL->verifHasPastell($transactionsInfo['authority_id']);
		} catch (Exception $e){
			$this->lastError = $e->getMessage();
			return false;
		}
		$id = $this->actesTransactionsSQL->updateStatus($id,ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,"En attente de l'envoi au SAE");
		return $id;
	}

	public function sendAllArchive(){
		echo "Début de l'envoie:\n";
		$actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
		$info_list = $actesTransactionsSQL->getArchiveFStatus(ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE);
		echo count($info_list)." transactions à envoyer...\n";
        $sigtermHandler = new SigTermHandler();
		foreach($info_list as $info){
			$transaction_id = $info['id'];
			echo "Envoi de la transaction $transaction_id : \n";
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
			echo "Impossible d'envoyer la transaction $id : " . $e->getMessage()."\n";
			$status_info = $this->actesTransactionsSQL->getLastStatusInfo($id);
			$first_try = strtotime($status_info['date']);
			if (time() - $first_try > self::PASSER_EN_ERREUR_APRES_NB_SECOND){
				$this->actesTransactionsSQL->updateStatus($id,ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,"Le document n'a pas pu être envoyé au SAE");
				echo "Passage de la transaction en erreur !\n";
			}
		}
	}

	private function sendArchiveThrow($id){
		$transactionsInfo = $this->actesTransactionsSQL->getInfo($id);
		$this->authoritySQL->verifHasPastell($transactionsInfo['authority_id']);

		$authoritySQL = new AuthoritySQL($this->sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($transactionsInfo['authority_id']);


		$pastell = $this->pastellFactory->getNewInstance(
			$authorityInfo['pastell_url'],
			$authorityInfo['pastell_id_e'],
			$authorityInfo['pastell_login'],
			$authorityInfo['pastell_password']
		);

		$id_d = $pastell->createActes($transactionsInfo);

		if (! $id_d){
			throw new Exception("Erreur pastell : ". $pastell->getLastError());
		}
		
		$actesFile = $this->actesTransactionsSQL->getAllFile($id);
		
		$actesEnvelopeSQL = new ActesEnvelopeSQL($this->sqlQuery);
		$actesEnvelopeInfo = $actesEnvelopeSQL->getInfo($transactionsInfo['envelope_id']);
		$enveloppe_path = $this->actesRetriever->getPath($actesEnvelopeInfo['file_path']);

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
			$pdftampone = $this->tamponerActe($tmp_folder,$actesFile[1]['filename'],$transactionsInfo['id']);
		}
		$pastell->postFile($id_d,"acte_tamponne",$pdftampone,"acte_tampone.".$path_parts['extension']);

		$datepostage = $actesTransactionsStatusInfo = $this->actesTransactionsSQL->getStatusInfo($transactionsInfo['id'],1);
		$pastell->setDatePostage($id_d,date("d/m/Y",strtotime($datepostage['date'])));

		//passer les paramètre
		$pdf=new ActesPdf();
		
		//construire le fichier pdf.
		$pdf->create_pdf($id);
		$pdf->output($tmp_folder."/bordereau_acquit","F");
		$pastell->postFile($id_d,"bordereau",$tmp_folder."/bordereau_acquit.pdf","bordereau_acquittement.pdf");
		
		array_shift($actesFile);
		array_shift($actesFile);
		
		foreach($actesFile as $file){
			$tgzExtractor->extract($enveloppe_path,$file['filename']);
			$pastell->postAnnexe($id_d, $tmp_folder.'/'.$file['filename'], $file['posted_filename']);
		}
		
		$actesTransactionsStatusInfo = $this->actesTransactionsSQL->getStatusInfo($id,4);

		if (! $actesTransactionsStatusInfo['flux_retour']){
			throw new Exception("L'AR acte n'est pas disponible");
		}
		
		file_put_contents($tmp_folder."/AR-{$acte_filename}", $actesTransactionsStatusInfo['flux_retour']);
		$pastell->postARActes($id_d,$tmp_folder."/AR-{$acte_filename}");
		
		
		$orig_acte_transaction_id = $transactionsInfo['id'];
		
		file_put_contents($tmp_folder."/empty", "");
	
		$relatedTransaction = $this->actesTransactionsSQL->getRelatedTransaction($id);
		$echange_prefecture_type = array();
		$echange_prefecture = array();
		$echange_prefecture_ar = array();
		foreach($relatedTransaction as $transaction){
			
			$actesEnvelopeInfo = $actesEnvelopeSQL->getInfo($transaction['envelope_id']);
			$actesFile = $this->actesTransactionsSQL->getAllFile($transaction['id']);
            $file_to_send = $this->actesRetriever->getPath($actesEnvelopeInfo['file_path']);
			if ($transaction['related_transaction_id'] == $orig_acte_transaction_id){
				//Transaction aller
				$echange_prefecture_type[] = $transaction['type'].'A';
				$filename = $actesFile[0]['filename'];
				$posted_filename = $actesFile[0]['posted_filename'];
				array_shift ($actesFile);
				$status_info =  $this->actesTransactionsSQL->getStatusInfo($transaction['id'],8);
			} else {
				//Transaction retour
				$echange_prefecture_type[] = $transaction['type'].'R';
				
				$filename = $actesFile[1]['filename'];
				$posted_filename = $actesFile[1]['posted_filename'];
				array_shift ($actesFile);
				array_shift ($actesFile);
				$status_info =  $this->actesTransactionsSQL->getStatusInfo($transaction['id'],11);
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
			throw new Exception($pastell->getLastError());
		}
		$this->actesTransactionsSQL->updateStatus($id,12,"Envoie de la transaction $id à Pastell");
		$this->actesTransactionsSQL->setSAETransferIdentifier($id,$id_d);
	}


	
	public function tamponerActe($tmpfolder,$fileorig,$transactionId){
		$pdftkise=$tmpfolder."/tampon_".$fileorig;

        $objectInstancier = ObjectInstancierFactory::getObjetInstancier();

		$acteTamponne = $objectInstancier->get("ActeTamponne");
		$tampon_content = $acteTamponne->tamponnerPDF($tmpfolder."/".$fileorig,$transactionId);

		file_put_contents($pdftkise,$tampon_content);
		return $pdftkise;
	}

	public function verifArchive($transactionInfo){
		echo "Transaction {$transactionInfo['unique_id']} : ";

		$authoritySQL = new AuthoritySQL($this->sqlQuery);
		$authorityInfo = $authoritySQL->getInfo($transactionInfo['authority_id']);

		if (! $authorityInfo['pastell_url'] ){
			echo  "La collectivité n'a pas de Pastell configuré\n";
			return false;
		}

		$pastell = $this->pastellFactory->getNewInstance(
			$authorityInfo['pastell_url'],
			$authorityInfo['pastell_id_e'],
			$authorityInfo['pastell_login'],
			$authorityInfo['pastell_password']
		);

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

		$actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);


		//s2lowif ($nodeName == 'ArchiveTransferAcceptance'){
        if ($nodeName == 'ArchiveTransferAcceptance' || ($nodeName == 'ArchiveTransferReply' && (strval($xml->{'ReplyCode'}) == '000'))){
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


}