<?php
class HeliosEnvoiControler {
	
	const P_APPLI = "GHELPES2";
	
	private $sqlQuery;
	private $heliosTransactionsSQL;
	private $authoritySQL;
	private $fichierCompteur;
	private $heliosTransmissionWindowsSQL;

	private $do_not_verify_nom_fic_unicity;
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
		$this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$this->authoritySQL = new AuthoritySQL($sqlQuery);
		$this->fichierCompteur = new FichierCompteur(HELIOS_COUNTER_FILE);
		$this->heliosTransmissionWindowsSQL = new HeliosTransmissionWindowsSQL($sqlQuery);
	}

	public function setDoNotVerifyNomFicUnicity($do_not_verify_nom_fic_unicity){
		$this->do_not_verify_nom_fic_unicity = $do_not_verify_nom_fic_unicity;
	}

	public function validateAllTransactions(){

		try {
			Antivirus::isAlive();
		} catch (Exception $e){
			echo $e->getMessage()."\n";
			return;
		}

		libxml_use_internal_errors(true);
		$transaction_id_list = $this->heliosTransactionsSQL->getIdsByStatus(HeliosTransactionsSQL::POSTE);
		
		foreach($transaction_id_list as $transaction_id){
			$transactionInfo = $this->heliosTransactionsSQL->getInfo($transaction_id);
		
			$message =  "Transaction $transaction_id en cours de traitement";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::EN_TRAITEMENT,$message,$transactionInfo['user_id']);
			
			$file_path = HELIOS_FILES_UPLOAD_ROOT."/".$transactionInfo['sha1'];
		
			$pes_content = file_get_contents($file_path);
			if (! $pes_content){
				$message = "Transaction $transaction_id : le fichier est introuvable";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}

			if (!Antivirus::checkArchiveSanity($file_path)) {
				$message = "Transaction $transaction_id : un virus a été detecté dans le fichier PES";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}

			//Pas de validation des fichier PES_Aller - Il ne s'agit pas d'une exigence.
			/*$heliosPESValidation = new HeliosPESValidation(HELIOS_XSD_PATH);
			if (! $heliosPESValidation->validate($pes_content)){
				print_r($heliosPESValidation->getLastError());
				$message = "Transaction $transaction_id : la transaction ne respecte pas le schéma PES_Aller";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}*/
			
			$pes_xml = simplexml_load_string($pes_content, 'SimpleXMLElement', LIBXML_PARSEHUGE);
			if (!$pes_xml){
				$message = "Transaction $transaction_id : ce fichier n'est pas en XML";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}

			$info_from_pes_aller = $this->extratInfoFromPESAller($pes_xml);

			$nom_fic = $info_from_pes_aller['nom_fic'];
			if (! $nom_fic){
				$message = "Transaction $transaction_id : La balise Enveloppe/Parametre/NomFic n'est pas présente ou est vide";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}
			$xadesSignature = new XadesSignature(XMLSEC1_PATH, new PKCS12(), new X509Certificate(), EXTENDED_VALIDCA_PATH);
			$heliosSignatureTechnique = new HeliosSignatureTechnique($this->heliosTransactionsSQL, HELIOS_FILES_UPLOAD_ROOT, $xadesSignature, HELIOS_ENABLE_SIGNATURE_TECHNIQUE);
			$xadesSignatureProperties = new XadesSignatureProperties();
			$xadesSignatureProperties->claimedRole = HELIOS_SIGNATURE_PLATEFORME_CLAIMED_ROLE;
			$xadesSignatureProperties->countryName = HELIOS_SIGNATURE_PLATEFORME_COUNTRY_NAME;
			$xadesSignatureProperties->postalCode = HELIOS_SIGNATURE_PLATEFORME_POSTAL_CODE;
			$xadesSignatureProperties->city = HELIOS_SIGNATURE_PLATEFORME_CITY;

			try {
				$heliosSignatureTechnique->sign($transaction_id, HELIOS_PLATEFORME_CERTIFICATE_P12, HELIOS_PLATEFORME_CERTIFICATE_PASSWORD, $xadesSignatureProperties);
			} catch (UnrecoverableHeliosSignatureTechniqueException $exception){
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$exception->getMessage(),$transactionInfo['user_id']);
				continue;
			} catch (RecoverableHeliosSignatureTechniqueException $exception){
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::POSTE,$exception->getMessage(),$transactionInfo['user_id']);
				continue;
			}
			$authorityInfo = $this->authoritySQL->getInfo($transactionInfo['authority_id']);

			if (! $this->verifNomFicUnicity($authorityInfo,$info_from_pes_aller)){
				$message = "Transaction $transaction_id : ce fichier existe déjà sur la plateforme";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}
			$this->heliosTransactionsSQL->setInfoFromPESAller($transaction_id,$info_from_pes_aller);


			$siret = $pes_xml->EnTetePES->IdColl['V'];
			$authoritySiret = new AuthoritySiretSQL($this->sqlQuery);
			$authoritySiret->add($transactionInfo['authority_id'],$siret);

			$message = "Transaction $transaction_id dans la file d'attente";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ATTENTE,$message,$transactionInfo['user_id']);
		}
		libxml_use_internal_errors(false);
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
	
	//nom du fichier à envoyer de la forme PESALR2_idColl_date_numOrdre.xml avec :
	//idColl : numéro siret de la collectivité,
	//date : date d'envoi à Helios sous la forme AAMMJJ,
	//numOrdr : numéro d'ordre d'envoi sur 3 chiffres.

	public function sendAllTransactions(){

		$file_sending_repository = HELIOS_FILES_UPLOAD_TMP;

		$transaction_id_list = $this->heliosTransactionsSQL->getIdsByStatus(HeliosTransactionsSQL::ATTENTE);

		$nb_file_send = 0;

		foreach($transaction_id_list as $transaction_id){
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
				continue;
			}

			$authorityInfo = $this->authoritySQL->getInfo($transactionInfo['authority_id']);

			$completeName = $this->createCompleteName($transactionInfo['siren']);
			$this->heliosTransactionsSQL->setCompleteName($transaction_id,$completeName);
			echo "Nom du fichier à envoyer : $completeName\n";
			$file_path = HELIOS_FILES_UPLOAD_ROOT."/".$transactionInfo['sha1'];

			$file_path_with_complete_name = $file_sending_repository."/".$completeName;
			if (! copy($file_path, $file_path_with_complete_name)){
				echo "Transaction $transaction_id : échec de la copie...: cp $file_path $file_path_with_complete_name";
				continue;
			}
			if (HELIOS_ZIP_BEFORE_SEND){
				$file_to_send = $file_sending_repository."/".$transactionInfo['sha1'].".zip";
				$zipArchive = new ZipArchive();
				if (! $zipArchive->open($file_to_send,ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)){
					echo "Transaction $transaction_id: Impossible d'ouvrir $file_to_send";
					continue;
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
				continue;
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
				continue;
			}

			if (! $authorityInfo["helios_ftp_dest"] || ! $authorityInfo["helios_ftp_login"] || ! $authorityInfo["helios_ftp_password"]){
				$message = "Transaction $transaction_id : les propriétés Helios FTP ne sont pas configurées correctement";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				unlink($file_path_with_complete_name);
				continue;
			}
			
			try {
				$ftp = new FTPFileSender();
				$ftp->connect(HELIOS_FTP_SERVER, HELIOS_FTP_PORT, $authorityInfo["helios_ftp_login"], $authorityInfo["helios_ftp_password"]);
				$ftp->setPassiveMode(HELIOS_FTP_PASSIVE_MODE);
				$ftp->sendRawCommand("site P_DEST {$authorityInfo["helios_ftp_dest"]}",HELIOS_SENDING_MODE_DEMO);
				$ftp->sendRawCommand("site P_APPLI ".self::P_APPLI,HELIOS_SENDING_MODE_DEMO);
				$ftp->sendRawCommand("site P_MSG $p_msg",HELIOS_SENDING_MODE_DEMO);
				$ftp->sendFile(HELIOS_SENDING_DESTINATION,$file_to_send);
				$ftp->disconnect();
			} catch (Exception $e){
				echo "Transaction $transaction_id: Erreur lors du postage de la transaction Helios $transaction_id : ".$e->getMessage()."\n";
				unlink($file_path_with_complete_name);
				continue;
			}

			$message = "Transaction $transaction_id transmise au serveur.";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::TRANSMIS,$message,$transactionInfo['user_id']);

			$this->heliosTransmissionWindowsSQL->addFile($transactionInfo['file_size']);

			if (HELIOS_ZIP_BEFORE_SEND){
				unlink($file_to_send);
			}
			unlink($file_path_with_complete_name);
			$nb_file_send++;
		}

		if ($nb_file_send == 0 && count($transaction_id_list)){
			$message = "Le script helios-envoi-fichier.php n'a pas envoyé de transactions sur les ".count($transaction_id_list)." à poster !\n";
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
		$info['nom_fic'] = utf8_decode(strval($pes_xml->Enveloppe->Parametres->NomFic['V']));
		$info['cod_col'] = $pes_xml->EnTetePES->CodCol['V'];
		$info['cod_bud'] = $pes_xml->EnTetePES->CodBud['V'];
		$info['id_post'] = $pes_xml->EnTetePES->IdPost['V'];
		return $info;
	}
	
}