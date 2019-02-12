<?php
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesTransaction.class.php');

/* Archive au sens SEDA et pas au sens Actes ... */

class ActesArchiveControler {

	private $sqlQuery;
	private $lastError;

	/** @var  PastellWrapperFactory */
	private $pastellWrapperFactory;

	/** @var  ActesTransactionsSQL */
	private $actesTransactionsSQL;

	/** @var AuthoritySQL  */
	private $authoritySQL;

	private $actesRetriever;

	/** @var PastellPropertiesSQL */
	private $pastellPropetiesSQL;

	/** @var S2lowLogger */
	private $logger;

	/** @var WorkerScript */
	private $workerScript;

	public function __construct(
	    SQLQuery $sqlQuery,
        ActesRetriever $actesRetriever,
		PastellPropertiesSQL $pastellPropertiesSQL,
		S2lowLogger $logger,
		WorkerScript $workerScript,
		PastellWrapperFactory $pastellWrapperFactory
    ){
		$this->sqlQuery = $sqlQuery;
		$this->pastellWrapperFactory = $pastellWrapperFactory;
		$this->actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);
		$this->authoritySQL = new AuthoritySQL($this->sqlQuery);
		$this->actesRetriever = $actesRetriever;
		$this->pastellPropetiesSQL = $pastellPropertiesSQL;
		$this->logger = $logger;
		$this->workerScript = $workerScript;
	}

	public function getLastError(){
		return $this->lastError;
	}

	public function setArchiveEnAttenteEnvoiSEA($user_id, $transaction_id,$put_in_job_queue = true){
		try {
			$transactionsInfo = $this->actesTransactionsSQL->getInfo($transaction_id);
			$this->logger->info("Préparation de l'envou au SAE pour l'actes $transaction_id - {$transactionsInfo['unique_id']} : en cours");

			$user = new User($user_id);
			$user->init();
			$this->isAllowToSendArchive($user_id,$transactionsInfo);

			if (!in_array($transactionsInfo['last_status_id'], array(4, 5, 14,20)) && $transactionsInfo['type'] != 1) {
				throw new Exception("Impossible d'archiver une transaction qui n'est pas en état « Acquittement reçu » ou « Validé ».");
			}
			$this->authoritySQL->verifHasPastell($transactionsInfo['authority_id']);
		} catch (Exception $e){
			$this->logger->error($e->getMessage());
			$this->lastError = $e->getMessage();
			return false;
		}
		$actes_transaction_workflow_id = $this->actesTransactionsSQL->updateStatus(
			$transaction_id,
			ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
			"En attente de l'envoi au SAE"
		);

		if ($put_in_job_queue) {
            $this->workerScript->putJobByClassName(
                ActesEnvoiSaeWorker::class, $transaction_id
            );
        }
		$this->logger->info("Préparation de l'envoi SAE pour l'actes $transaction_id - {$transactionsInfo['unique_id']} : OK");
		return $actes_transaction_workflow_id;
	}

	/**
	 * @param $user_id
	 * @param $transactionsInfo
	 * @return bool
	 * @throws Exception
	 */
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


	public function getAllTransactionIdToSend($authority_id = 0){
		$actesTransactionsSQL = new ActesTransactionsSQL($this->sqlQuery);

		if (! $authority_id){
            return $actesTransactionsSQL->getTransactionToSendSAE();
        }

		$info_list = $actesTransactionsSQL->getArchiveFStatus(
			ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
			$authority_id
		);
		$transaction_id_list = [];
		foreach($info_list as $info) {
			$transaction_id_list[] = $info['id'];
		}
		return $transaction_id_list;
	}


	/**
	 * @param $id
	 * @throws Exception
	 */
	public function sendArchive($id){
		$id_d = false;
		$tmpFolder = new TmpFolder();
		$tmp_folder = $tmpFolder->create();
		try {
			$this->logger->info("Envoi de La transaction $id sur le SAE");
			$id_d = $this->createPastellDocument($id);
			if ($id_d) {
				$this->sendArchiveThrow($id, $id_d, $tmp_folder);
				$this->logger->info("La transaction $id a été envoyé sur le SAE (id_d pastell : $id_d)");
			}
		} catch (Exception $e){
			$message = "Impossible d'envoyer la transaction $id : " . $e->getMessage();
			if ($id_d){
				$this->deletePastellDocument($id,$id_d);
				$message .=  " - id_d=$id_d";
			}
			$this->logger->error($message);
			$this->actesTransactionsSQL->updateStatus(
				$id,
				ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
				$message
			);
		}
		$tmpFolder->delete($tmp_folder);
	}


	private function deletePastellDocument($transaction_id, $id_d){
		$transactionsInfo = $this->actesTransactionsSQL->getInfo($transaction_id);
		$pastellProperties = $this->pastellPropetiesSQL->getPastellProperties($transactionsInfo['authority_id']);

		$pastell = $this->pastellWrapperFactory->getNewInstance($pastellProperties);
		try {
			$pastell->delete($id_d);
		} catch (Exception $e){
			$this->logger->alert("Impossible de supprimer le document id_d sur {$pastellProperties->url} !");
			return false;
		}
		return true;
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws Exception
	 */
	private function createPastellDocument($id){
		$transactionsInfo = $this->actesTransactionsSQL->getInfo($id);
		if($transactionsInfo['last_status_id'] != ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE){
			$this->logger->error("La transaction $id à envoyer au SAE n'est pas dans le bon status ! {$transactionsInfo['last_status_id']} trouvé");
			return false;
		}

		$this->authoritySQL->verifHasPastell($transactionsInfo['authority_id']);

		$pastellProperties = $this->pastellPropetiesSQL->getPastellProperties($transactionsInfo['authority_id']);
		$pastell = $this->pastellWrapperFactory->getNewInstance($pastellProperties);

		$id_d = $pastell->createActes($transactionsInfo);

		if (! $id_d){
			throw new Exception("Erreur pastell : ". $pastell->getLastError());
		}
		$this->logger->debug("Création du document sur Pastell id_d=$id_d");

		return $id_d;
	}

	/**
	 * @param $id
	 * @param $id_d
	 * @param $tmp_folder
	 * @throws Exception
	 */
	private function sendArchiveThrow($id,$id_d,$tmp_folder){

		$transactionsInfo = $this->actesTransactionsSQL->getInfo($id);
		$this->authoritySQL->verifHasPastell($transactionsInfo['authority_id']);

		$pastellProperties = $this->pastellPropetiesSQL->getPastellProperties($transactionsInfo['authority_id']);

		$pastell = $this->pastellWrapperFactory->getNewInstance($pastellProperties);


		$this->logger->debug("Envoi de la transaction $id sur Pastell {$pastellProperties->url} id_e={$pastellProperties->id_e}");


		$actesFile = $this->actesTransactionsSQL->getAllFile($id);
		
		$actesEnvelopeSQL = new ActesEnvelopeSQL($this->sqlQuery);
		$actesEnvelopeInfo = $actesEnvelopeSQL->getInfo($transactionsInfo['envelope_id']);
		$enveloppe_path = $this->actesRetriever->getPath($actesEnvelopeInfo['file_path']);


		
		$tgzExtractor = new TGZExtractor($tmp_folder);
		$tgzExtractor->extract($enveloppe_path,$actesFile[1]['filename']);
		
		$acte_filename = $actesFile[0]['filename'];
		
		$pastell->postActes($id_d,$tmp_folder."/".$actesFile[1]['filename'],$actesFile[1]['posted_filename']);
		$this->logger->debug("postage de l'actes id_d : $id_d");
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


		$this->logger->debug("Envoi au SAE : $id_d");

		$result = $pastell->sendSAE($id_d,$pastellProperties->actes_action);
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




}