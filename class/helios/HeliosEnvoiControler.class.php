<?php
class HeliosEnvoiControler {
	
	const P_APPLI = "GHELPES2";
	
	private $sqlQuery;
	private $heliosTransactionsSQL;
	private $authoritySQL;
	private $fichierCompteur;
	private $heliosTransmissionWindowsSQL;

	private $do_not_verify_nom_fic_unicity;

	private $pesAllerRetriever;

	private $helios_files_upload_root;

	private $antivirus;

	private $workerScript;
	/** @var FTPHeliosSender  */
    private $FTPHeliosSender;

    public function __construct(
        SQLQuery $sqlQuery,
        PesAllerRetriever $pesAllerRetriever,
        $helios_files_upload_root,
        Antivirus $antivirus,
        WorkerScript $workerScript,
        FTPHeliosSender $FTPHeliosSender
    ){
		$this->sqlQuery = $sqlQuery;
		$this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$this->authoritySQL = new AuthoritySQL($sqlQuery);
		$this->fichierCompteur = new FichierCompteur(HELIOS_COUNTER_FILE);
		$this->heliosTransmissionWindowsSQL = new HeliosTransmissionWindowsSQL($sqlQuery);
		$this->pesAllerRetriever = $pesAllerRetriever;
		$this->helios_files_upload_root = $helios_files_upload_root;
		$this->antivirus = $antivirus;
		$this->workerScript = $workerScript;
        $this->FTPHeliosSender = $FTPHeliosSender;
	}

	public function setDoNotVerifyNomFicUnicity($do_not_verify_nom_fic_unicity){
		$this->do_not_verify_nom_fic_unicity = $do_not_verify_nom_fic_unicity;
	}


	public function validateOneTransaction($transaction_id){
		libxml_use_internal_errors(true);
		$transactionInfo = $this->heliosTransactionsSQL->getInfo($transaction_id);

		$file_path = $this->pesAllerRetriever->getPath($transactionInfo['sha1']);

		$pes_content = file_get_contents($file_path);
		if (! $pes_content){
			$message = "Transaction $transaction_id : le fichier est introuvable";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			return;
		}

		if (!$this->antivirus->checkArchiveSanity($file_path)) {
			$message = "Transaction $transaction_id : un virus a été detecté dans le fichier PES";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			return;
		}

		//Pas de validation des fichier PES_Aller - Il ne s'agit pas d'une exigence.
		/*$heliosPESValidation = new HeliosPESValidation(HELIOS_XSD_PATH);
		if (! $heliosPESValidation->validate($pes_content)){
			print_r($heliosPESValidation->getLastError());
			$message = "Transaction $transaction_id : la transaction ne respecte pas le schéma PES_Aller";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			continue;
		}*/

		if (! $this->isInIso8859($pes_content)){
			$message = "Transaction $transaction_id : ce fichier n'est pas encodé en ISO-8859-1";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			return;
		}

		$pes_xml = simplexml_load_string($pes_content, 'SimpleXMLElement', LIBXML_PARSEHUGE);
		if (!$pes_xml){
			$message = "Transaction $transaction_id : ce fichier n'est pas en XML";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			return;
		}

		if ($this->isPESEmpty($pes_xml)){
			$message = "Transaction $transaction_id : ce fichier ne contient ni bordereau, ni PJ, ni marché";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			return;
		}

		$info_from_pes_aller = $this->extratInfoFromPESAller($pes_xml);

		$nom_fic = $info_from_pes_aller['nom_fic'];
		if (! $nom_fic){
			$message = "Transaction $transaction_id : La balise Enveloppe/Parametre/NomFic n'est pas présente ou est vide";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			return;
		}
        $verifyPemFactory = new VerifyPemCertificateFactory();
		$xadesSignature = new XadesSignature(
		    XMLSEC1_PATH, new PKCS12(),
            new X509Certificate(),
            EXTENDED_VALIDCA_PATH,
            new XadesSignatureParser(),
            new PemCertificateFactory(),
            $verifyPemFactory->get(EXTENDED_VALIDCA_PATH)
        );
		$heliosSignatureTechnique = new HeliosSignatureTechnique(
			$this->heliosTransactionsSQL,
			$this->helios_files_upload_root,
			$xadesSignature,
			HELIOS_ENABLE_SIGNATURE_TECHNIQUE,
			$this->pesAllerRetriever
		);
		$xadesSignatureProperties = new XadesSignatureProperties();
		$xadesSignatureProperties->claimedRole = HELIOS_SIGNATURE_PLATEFORME_CLAIMED_ROLE;
		$xadesSignatureProperties->countryName = HELIOS_SIGNATURE_PLATEFORME_COUNTRY_NAME;
		$xadesSignatureProperties->postalCode = HELIOS_SIGNATURE_PLATEFORME_POSTAL_CODE;
		$xadesSignatureProperties->city = HELIOS_SIGNATURE_PLATEFORME_CITY;

		try {
			$heliosSignatureTechnique->sign($transaction_id, HELIOS_PLATEFORME_CERTIFICATE_P12, HELIOS_PLATEFORME_CERTIFICATE_PASSWORD, $xadesSignatureProperties);
		} catch (UnrecoverableHeliosSignatureTechniqueException $exception){
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$exception->getMessage(),$transactionInfo['user_id']);
			return;
		} catch (RecoverableHeliosSignatureTechniqueException $exception){
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::POSTE,$exception->getMessage(),$transactionInfo['user_id']);
			return;
		}
		$authorityInfo = $this->authoritySQL->getInfo($transactionInfo['authority_id']);

		if (! $this->verifNomFicUnicity($authorityInfo,$info_from_pes_aller)){
			$message = "Transaction $transaction_id : ce fichier existe déjà sur la plateforme";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			return;
		}
		$this->heliosTransactionsSQL->setInfoFromPESAller($transaction_id,$info_from_pes_aller);


		$siret = $pes_xml->EnTetePES->IdColl['V'];
		$authoritySiret = new AuthoritySiretSQL($this->sqlQuery);
		$authoritySiret->add($transactionInfo['authority_id'],$siret);

		$message = "Transaction $transaction_id dans la file d'attente";
		$this->updateStatus($transaction_id,HeliosTransactionsSQL::ATTENTE,$message,$transactionInfo['user_id']);

		$this->workerScript->putJobByClassName(HeliosEnvoiWorker::class,$transaction_id);
		libxml_use_internal_errors(false);
	}

	/**
	 * @throws Exception
	 */
	public function validateAllTransactions(){
		try {
			$this->antivirus->isAlive();
		} catch (Exception $e){
			echo $e->getMessage()."\n";
			return;
		}

		$transaction_id_list = $this->heliosTransactionsSQL->getIdsByStatus(HeliosTransactionsSQL::POSTE);
		$sigtermHandler = SigTermHandler::getInstance();
		foreach($transaction_id_list as $transaction_id){
			if ($sigtermHandler->isSigtermCalled()){
				break;
			}
			$this->validateOneTransaction($transaction_id);
		}
	}

	private function isInIso8859($pes_content){
		$first_line = substr($pes_content,0,50);
		return preg_match("#ISO-8859-1#i",$first_line);
	}

	private function verifNomFicUnicity($authorityInfo,$info_from_pes_aller){
		if ($this->do_not_verify_nom_fic_unicity) { //ARE YOU SURE ?
			if ($authorityInfo['helios_do_not_verify_nom_fic_unicity']){ //VERY SURE ?
				//OK, SO LET'S GO...
				return true;
			}
		}
		return ! $this->heliosTransactionsSQL->nomFicExists(
			$info_from_pes_aller['nom_fic'],
			$info_from_pes_aller['cod_col']
		);
	}
	
	private function updateStatus($transaction_id,$status_id,$message,$user_id){
		echo $message."\n";
		$this->heliosTransactionsSQL->updateStatus($transaction_id,$status_id,$message);
		Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, 'USER', 'helios',false, $user_id);
	}

	public function sendOneTransaction($transaction_id){
		$file_sending_repository = HELIOS_FILES_UPLOAD_TMP;

		echo "Préparation de l'envoi de la transaction $transaction_id\n";
		$transactionInfo = $this->heliosTransactionsSQL->getInfo($transaction_id);

		if (! $this->heliosTransmissionWindowsSQL->canSend($transactionInfo['file_size'])){
			echo "La fenêtre d'envoie est pleine \n";
			if (! $transactionInfo['warning_sent'] && $this->heliosTransactionsSQL->mustSendWarning($transaction_id)){
				$message = "La transaction Helios $transaction_id est en attente depuis plus de 48H !";
				Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, 'USER', 'helios',false, $transactionInfo['user_id']);
				echo $message."\n";
				mail(EMAIL_ADMIN,"Transaction Helios bloqué",$message,"From: ".TDT_FROM_EMAIL);
				$this->heliosTransactionsSQL->setSendWarning($transaction_id);
			}
			return;
		}

		$authorityInfo = $this->authoritySQL->getInfo($transactionInfo['authority_id']);

		$completeName = $this->createCompleteName($transactionInfo['siren']);
		$this->heliosTransactionsSQL->setCompleteName($transaction_id,$completeName);
		echo "Nom du fichier à envoyer : $completeName\n";

		$file_path = $this->pesAllerRetriever->getPath($transactionInfo['sha1']);

		$file_path_with_complete_name = $file_sending_repository."/".$completeName;
		if (! copy($file_path, $file_path_with_complete_name)){
			echo "Transaction $transaction_id : échec de la copie...: cp $file_path $file_path_with_complete_name";
			return;
		}
		if (HELIOS_ZIP_BEFORE_SEND){
			$file_to_send = $file_sending_repository."/".$transactionInfo['sha1'].".zip";
			$zipArchive = new ZipArchive();
			if (! $zipArchive->open($file_to_send,ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)){
				echo "Transaction $transaction_id: Impossible d'ouvrir $file_to_send";
				return;
			}
			$zipArchive->addFile($file_path_with_complete_name,$completeName);
			$zipArchive->close();
		} else {
			$file_to_send = $file_path_with_complete_name;
		}

		$sha1_file = sha1_file($file_path);
		if ($sha1_file != $transactionInfo['sha1']){
			$message = "Transaction $transaction_id : le fichier a été altéré depuis son postage ou sa signature sur la plateforme\n";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			return;
		}

		$pesAller = new PesAller();
		try {
			$p_msg = $pesAller->getP_MSG($file_path);
		} catch (Exception $e){
			$message = "Transaction $transaction_id : ".$e->getMessage();
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			if (HELIOS_ZIP_BEFORE_SEND){
				unlink($file_to_send);
			}
			unlink($file_path_with_complete_name);
			return;
		}

		if (! $authorityInfo["helios_ftp_dest"]){
			$message = "Transaction $transaction_id : les propriétés Helios FTP ne sont pas configurées correctement";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
			unlink($file_path_with_complete_name);
			return;
		}

		try {
            $this->FTPHeliosSender->sendFile($authorityInfo["helios_ftp_dest"], $p_msg, $file_to_send);
        } catch (Exception $e){
			echo "Transaction $transaction_id: Erreur lors du postage de la transaction Helios $transaction_id : ".$e->getMessage()."\n";
			unlink($file_path_with_complete_name);
			return;
		}

		$message = "Transaction $transaction_id transmise au serveur.";
		$this->updateStatus($transaction_id,HeliosTransactionsSQL::TRANSMIS,$message,$transactionInfo['user_id']);

		$this->heliosTransmissionWindowsSQL->addFile($transactionInfo['file_size']);

		if (HELIOS_ZIP_BEFORE_SEND){
			unlink($file_to_send);
		}
		unlink($file_path_with_complete_name);
	}


	//nom du fichier à envoyer de la forme PESALR2_idColl_date_numOrdre.xml avec :
	//idColl : numéro siret de la collectivité,
	//date : date d'envoi à Helios sous la forme AAMMJJ,
	//numOrdr : numéro d'ordre d'envoi sur 3 chiffres.
	public function sendAllTransactions(){


		$transaction_id_list = $this->heliosTransactionsSQL->getIdsByStatus(HeliosTransactionsSQL::ATTENTE);

		$nb_file_send = 0;

		echo "Il y a ".count($transaction_id_list)." transactions à envoyer\n";

		foreach($transaction_id_list as $transaction_id){
			$this->sendOneTransaction($transaction_id);

			$nb_file_send++;
		}

		if ($nb_file_send == 0 && count($transaction_id_list)){
			$message = "Le script helios-reception-envoi.php n'a pas envoyé de transactions sur les ".count($transaction_id_list)." à poster !\n";
			echo $message;
			mail(EMAIL_ADMIN,"[ALERTE CRITIQUE] L'envoi des PES à la DGFiP ne fonctionne plus",$message,"From: ".TDT_FROM_EMAIL);
		}

	}
	
	private function createCompleteName($siren) {
		$numero = $this->fichierCompteur->getNumero();
		$date = date("ymd");
		return "PESALR2_{$siren}_{$date}_{$numero}.xml";
	}


	public function rollback($transaction_id){

	}

	public function extratInfoFromPESAller(SimpleXMLElement $pes_xml){
		$info['nom_fic'] = strval($pes_xml->Enveloppe->Parametres->NomFic['V']);
		$info['cod_col'] = strval($pes_xml->EnTetePES->CodCol['V']);
		$info['cod_bud'] = strval($pes_xml->EnTetePES->CodBud['V']);
		$info['id_post'] = strval($pes_xml->EnTetePES->IdPost['V']);
		return $info;
	}


    /**
     * Les fichiers qui ne contiennent ni bordereau, ni PJ, ni marché ne généère pas d'acquittement
     * et donc ne sont jamais ni acquitter ni en erreur
     * @param SimpleXMLElement $pes_xml
     * @return bool
     */
	public function isPESEmpty(SimpleXMLElement $pes_xml) : bool{
	    $r = [];
        foreach($pes_xml->children() as $child){
            $r[] = $child->getName();
        }
        $r = array_diff($r,['Enveloppe','EnTetePES']);
        return ! boolval($r);
    }
}