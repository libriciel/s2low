<?php
class HeliosEnvoiControler {
	
	const P_APPLI = "GHELPES2";
	
	private $sqlQuery;
	private $heliosTransactionsSQL;
	private $authoritySQL;
	private $fichierCompteur;
	private $heliosTransmissionWindowsSQL;
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
		$this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->sqlQuery);
		$this->authoritySQL = new AuthoritySQL($sqlQuery);
		$this->fichierCompteur = new FichierCompteur(HELIOS_COUNTER_FILE);
		$this->heliosTransmissionWindowsSQL = new HeliosTransmissionWindowsSQL($sqlQuery);
	}
	
	public function validateAllTransactions(){
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
		
			/*$xsdValidation = new XSDValidation(__DIR__."/../../xsd/helios/PES_V2/Rev0/PES_Aller.xsd");
			if (! $xsdValidation->validate($pes_content)){
				$this->displayXMLError();
				$message = "Transaction $transaction_id : la transaction ne respecte pas le schéma PES_Aller";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}*/
			
			$pes_xml = simplexml_load_string($pes_content);			
			if (!$pes_xml){
				$message = "Transaction $transaction_id : ce fichier n'est pas en XML";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}
			
			
			$nom_fic = strval($pes_xml->Enveloppe->Parametres->NomFic['V']);
			if (! $nom_fic){
				$message = "Transaction $transaction_id : La balise Enveloppe/Parametre/NomFic n'est pas présente ou est vide";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}
			if ($this->heliosTransactionsSQL->nomFicExists($nom_fic)){
				$message = "Transaction $transaction_id : ce fichier existe déjà sur la plateforme";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}
			$this->heliosTransactionsSQL->setNomFic($transaction_id,$nom_fic);
			
			$message = "Transaction $transaction_id dans la file d'attente";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::ATTENTE,$message,$transactionInfo['user_id']);
		}
		libxml_use_internal_errors(false);
	}
	
	public function sendAllTransactions(){
		$transaction_id_list = $this->heliosTransactionsSQL->getIdsByStatus(HeliosTransactionsSQL::ATTENTE);
		foreach($transaction_id_list as $transaction_id){
			echo "Préparation de l'envoi de la transaction $transaction_id\n";
			$transactionInfo = $this->heliosTransactionsSQL->getInfo($transaction_id);
			
			if (! $this->heliosTransmissionWindowsSQL->canSend($transactionInfo['file_size'])){
				echo "La fenêtre d'envoie est pleine \n";
				if (! $transactionInfo['warning_sent'] && $this->heliosTransactionsSQL->mustSendWarning($transaction_id)){
					$message = "La transaction Helios $transaction_id est en attente depuis plus de 48H !";					
					Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, 'USER', 'helios',false, $transactionInfo['user_id']);
					echo $message."\n";
					mail(EMAIL_ADMIN,"Transaction Helios bloqué",$message);
					$this->heliosTransactionsSQL->setSendWarning($transaction_id);
				}
				continue;
			}
			
			$authorityInfo = $this->authoritySQL->getInfo($transactionInfo['authority_id']);
			
			$completeName = $this->createCompleteName($transactionInfo['siren']);
			$this->heliosTransactionsSQL->setCompleteName($transaction_id,$completeName);
			echo "Nom du fichier à envoyer : $completeName\n";
			$file_path = HELIOS_FILES_UPLOAD_ROOT."/".$transactionInfo['sha1'];
			$file_path_with_complete_name = HELIOS_FILES_UPLOAD_ROOT."/".$completeName; 
			if (! copy($file_path, $file_path_with_complete_name)){
				echo "Transaction $transaction_id : échec de la copie...: cp $file_path $file_path_with_complete_name";
				continue;
			}			
			if (HELIOS_ZIP_BEFORE_SEND){
				$file_to_send = HELIOS_FILES_UPLOAD_ROOT."/".$transactionInfo['sha1'].".zip";
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
				echo "Transaction $transaction_id : le fichier a été altéré depuis son postage ou sa signature sur la plateforme";
				continue;
			}

			$pes_xml = simplexml_load_file($file_path, 'SimpleXMLElement', LIBXML_PARSEHUGE);
			$cod_col = $pes_xml->EnTetePES->CodCol['V'];
			if (! $cod_col){
				$message = "Transaction $transaction_id : La balise EnTetePES/CodCol n'est pas présente ou est vide";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}			
			$id_post = $pes_xml->EnTetePES->IdPost['V'];
			if (! $id_post){
				$message = "Transaction $transaction_id : La balise EnTetePES/IdPost n'est pas présente ou est vide";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}
			$cod_bud = $pes_xml->EnTetePES->CodBud['V'];
			if (! $cod_bud){
				$message = "Transaction $transaction_id : La balise EnTetePES/CodBud n'est pas présente ou est vide";
				$this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
				continue;
			}
			$p_msg = "PES#" . $cod_col . "#" . $id_post . "#" . $cod_bud;
			
			try {
				$ftp = new FTPFileSender();
				$ftp->connect(HELIOS_FTP_SERVER, HELIOS_FTP_PORT, $authorityInfo["helios_ftp_login"], $authorityInfo["helios_ftp_password"]);
				$ftp->setPassiveMode(true);
				$ftp->sendRawCommand("site P_DEST {$authorityInfo["helios_ftp_dest"]}",HELIOS_SENDING_MODE_DEMO);
				$ftp->sendRawCommand("site P_APPLI ".self::P_APPLI,HELIOS_SENDING_MODE_DEMO);
				$ftp->sendRawCommand("site P_MSG $p_msg",HELIOS_SENDING_MODE_DEMO);
				$ftp->sendFile(HELIOS_SENDING_DESTINATION,$file_to_send);
				$ftp->disconnect();
			} catch (Exception $e){
				echo "Transaction $transaction_id: Erreur lors du postage de la transaction Helios $transaction_id : ".$e->getMessage()."\n";
				continue;
			}
			
			$message = "Transaction $transaction_id transmise au serveur.";
			$this->updateStatus($transaction_id,HeliosTransactionsSQL::TRANSMIS,$message,$transactionInfo['user_id']);
			
			$this->heliosTransmissionWindowsSQL->addFile($transactionInfo['file_size']);
			
			if (HELIOS_ZIP_BEFORE_SEND){
				unlink($file_to_send);
			}
			unlink($file_path_with_complete_name);
		}
	}
	
	//nom du fichier à envoyer de la forme PESALR2_idColl_date_numOrdre.xml avec :
	//idColl : numéro siret de la collectivité,
	//date : date d'envoi à Helios sous la forme AAMMJJ,
	//numOrdr : numéro d'ordre d'envoi sur 3 chiffres.
	private function createCompleteName($siren) {
		$numero = $this->fichierCompteur->getNumero();
		$date = date("ymd");
		return "PESALR2_{$siren}_{$date}_{$numero}.xml";			
	}
	
	private function displayXMLError(){
		echo "Erreur dans la validation du fichier PES : \n";
		print_r(libxml_get_errors());
		libxml_clear_errors();
	}
	
	private function updateStatus($transaction_id,$status_id,$message,$user_id){
		echo $message."\n";
		$this->heliosTransactionsSQL->updateStatus($transaction_id,$status_id,$message);
		Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, 'USER', 'helios',false, $user_id);
	}
	
}