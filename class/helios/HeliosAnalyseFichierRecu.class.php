<?php

class HeliosAnalyseFichierRecu {
	
	const ID = "Helios Analyse Fichier Réception";
	const MAX_FILE_SIZE = 150 * 1024 * 1024;
	
	private $heliosTransactionsSQL;
	private $authoritySQL;
	private $heliosRetourSQL;
	private $authoritySiretSQL;
	private $schema_pes_path;
	private $email_admin;
	private $email_from;
	private $s2lowLogger;

	public function __construct(
		HeliosTransactionsSQL $heliosTransactionsSQL,
		AuthoritySQL $authoritySQL,
		HeliosRetourSQL $heliosRetourSQL,
		AuthoritySiretSQL $authoritySiretSQL,
		$schema_pes_path,
		$email_admin_technique,
		$tdt_from_email,
		S2lowLogger $s2lowLogger
	){
		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
		$this->authoritySQL = $authoritySQL;
		$this->heliosRetourSQL = $heliosRetourSQL;
		$this->schema_pes_path = $schema_pes_path;
		$this->email_admin = $email_admin_technique;
		$this->authoritySiretSQL = $authoritySiretSQL;
		$this->email_from = $tdt_from_email;
		$this->s2lowLogger = $s2lowLogger;
	}

	public function getAllDirectory($helios_ftp_response_tmp_local_path){
		$helios_ftp_response_tmp_local_path = rtrim($helios_ftp_response_tmp_local_path,"/")."/";

		$this->s2lowLogger->info("Analyse du répertoire : $helios_ftp_response_tmp_local_path");

		$file_list = scandir($helios_ftp_response_tmp_local_path);

		if ($file_list === false){
			$this->s2lowLogger->critical("[ECHEC] Erreur lors de la lecture du répertoire  $helios_ftp_response_tmp_local_path");
			return [];
		}

		$file_list = array_diff($file_list, array('..', '.'));

		if (!$file_list){
			$this->s2lowLogger->info("Aucun fichier à analyser");
			return [];
		}
		$this->s2lowLogger->info("Traitement de ".count($file_list)." fichiers trouvés");
		return $file_list;
	}

	
	public function analyse($helios_ftp_response_tmp_local_path, $helios_response_root,$helios_responses_error_path,$ocre_file_path){


		$helios_ftp_response_tmp_local_path = rtrim($helios_ftp_response_tmp_local_path,"/")."/";

		$file_list = $this->getAllDirectory($helios_ftp_response_tmp_local_path);
		if (! $file_list){
			return;
		}

		$erreur_list = array();
		$sigtermHandler = SigTermHandler::getInstance();
		foreach($file_list as $file){
			try {
				$this->analyseOneFile($helios_ftp_response_tmp_local_path.$file,$helios_response_root,$ocre_file_path);
                if ($sigtermHandler->isSigtermCalled()){
                    break;
                }
			} catch (Exception $e){
				$this->s2lowLogger->error("[ERREUR] ". $e->getMessage());
				$erreur_list[$file] = $e->getMessage();
			}
		}

		if ($erreur_list){
			$subject = "[S2low][Helios] Des fichiers sont en erreur sur le script de récupération des fichier PES_Acquit/PES_Retour";
			$msg = "";
			foreach($erreur_list as $file => $message){
				$msg.= "Fichier : $file => $message\n";
			}
			$msg .= "\n\nLes fichiers en erreur sont disponible dans le répertoire $helios_responses_error_path\n";
			$this->sendMailToAdmin($subject, $msg);
		}

		foreach($erreur_list as $file => $message){
			if (file_exists($helios_responses_error_path."/".$file)){
                $i = 0;
                do {
                    $i++;
                    $file_num = "$helios_responses_error_path/$file.$i";
                } while (file_exists($file_num));
				$this->s2lowLogger->warning("[WARNING] Le fichier $file existe déjà dans le répertoire des fichiers en erreur : renommé en *.$i");
                rename($helios_responses_error_path."/".$file,$file_num);
			}
			rename($helios_ftp_response_tmp_local_path."/".$file,$helios_responses_error_path."/".$file);
		}
	}

	/**
	 * @param $helios_ftp_response_tmp_local_path
	 * @param $helios_response_root
	 * @param $helios_responses_error_path
	 * @param $ocre_file_path
	 * @param $filename
	 * @throws Exception
	 */
	public function analyseOneFileForWorker($helios_ftp_response_tmp_local_path, $helios_response_root,$helios_responses_error_path,$ocre_file_path,$filename){
		$helios_ftp_response_tmp_local_path = rtrim($helios_ftp_response_tmp_local_path,"/")."/";

		$erreur_list = [];
		try {
			$this->analyseOneFile($helios_ftp_response_tmp_local_path . $filename, $helios_response_root, $ocre_file_path);
		} catch (Exception $e){
			$this->s2lowLogger->error("[ERREUR] ". $e->getMessage());
			$erreur_list[$filename] = $e->getMessage();
		}
		if ($erreur_list){
			$subject = "[S2low][Helios] Des fichiers sont en erreur sur le script de récupération des fichier PES_Acquit/PES_Retour";
			$msg = "";
			foreach($erreur_list as $file => $message){
				$msg.= "Fichier : $file => $message\n";
			}
			$msg .= "\n\nLes fichiers en erreur sont disponible dans le répertoire $helios_responses_error_path\n";
			$this->sendMailToAdmin($subject, $msg);
		}

		foreach($erreur_list as $file => $message){
			if (file_exists($helios_responses_error_path."/".$file)){
				$i = 0;
				do {
					$i++;
					$file_num = "$helios_responses_error_path/$file.$i";
				} while (file_exists($file_num));
				$this->s2lowLogger->warning("[WARNING] Le fichier $file existe déjà dans le répertoire des fichiers en erreur : renommé en *.$i");
				rename($helios_responses_error_path."/".$file,$file_num);
			}
			rename($helios_ftp_response_tmp_local_path."/".$file,$helios_responses_error_path."/".$file);
		}
	}


	/**
	 * @param $file_path
	 * @param $helios_response_root
	 * @param $ocre_file_path
	 * @param bool $validate_xsd
	 * @throws Exception
	 */
	public function analyseOneFile($file_path,$helios_response_root,$ocre_file_path,$validate_xsd = true){
		$basename = basename($file_path);
		$this->s2lowLogger->info("Traitement de $file_path");

		if (preg_match("#.ocre?$#",strtolower($basename))){
			if (! rename($file_path,$ocre_file_path."/".$basename)){
				throw new Exception(" Le fichier $file_path n'a pas pu être déplacé !");
			}
			return;
		}

		$file_size = filesize($file_path);
		$this->s2lowLogger->debug("Taille du fichier $file_path en octets : $file_size");
		if ($file_size > self::MAX_FILE_SIZE ){
			throw new Exception(" La taille du fichier $file_path ($file_size octets) dépasse la taille maximale (".self::MAX_FILE_SIZE." octets) !");
		}


		libxml_clear_errors();
		$xml = simplexml_load_file($file_path);
		if (! $xml){
			throw new Exception("Le fichier $basename n'est pas bien formé (fichier ignoré)");
		}
		$root_name = strtolower($xml->getName());

		if ($root_name == 'pes_retour'){
			$schema_location = $this->schema_pes_path."/PES_V2/RETOUR/Rev0/PES_Retour.xsd";
		} else {
			$schema_location = $this->schema_pes_path."/PES_V2/Rev0/PES_V2_Acquit_Autonome_V2.xsd";
		}

		libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->load($file_path);

		$errors = libxml_get_errors();
		libxml_clear_errors();

		if ($errors){
			throw new Exception("Le fichier $basename n'est pas bien formé (fichier ignoré)");
		}
		$dom->schemaValidate($schema_location);
		$errors = libxml_get_errors();
		libxml_clear_errors();

		if ($errors && $validate_xsd){
			$this->s2lowLogger->error("Erreur lors de la validation du schéma XML");
			$this->s2lowLogger->error(json_encode($errors));
			$root_name= "validation_error";
		}

		$this->heliosTransactionsSQL->begin();
		switch($root_name){
			case 'pes_acquit': $this->traitementAck($basename,$xml); break;
			case 'pes_nonacquit': $this->traitementNack($basename,$xml); break;
			case 'pes_retour' : $this->traitementPESRetour($basename,$xml); break;
			case 'validation_error'  : $this->traitementErreur($basename,$xml); break;
			default: throw new Exception("$basename : Type PES retour inconnu : $root_name (fichier ignoré)");
		}
        $renameSuccess = false;

		try{
            $renameSuccess = rename($file_path, $helios_response_root . "/" . $basename);
        } catch (Exception $e){

        }
        if (!$renameSuccess){
            $this->s2lowLogger->error("Traitement de $file_path annulé : déplacement impossible");
		    $this->heliosTransactionsSQL->rollback();
		}
        else{
            $this->heliosTransactionsSQL->commit();
        }
	}

	/**
	 * @param $basename
	 * @param SimpleXMLElement $xml
	 * @throws Exception
	 */
	private function traitementErreur($basename,SimpleXMLElement $xml){
		$helios_transaction_id = $this->retrieveTransaction($xml,false);

		if (! $helios_transaction_id){
			throw new Exception("Le fichier $basename n'est pas valide et aucun NomFic n'a peu être extrait");
		}

		$message = "Transaction $helios_transaction_id : erreur retournée par Helios";
		$this->heliosTransactionsSQL->updateStatus(
			$helios_transaction_id,
			HeliosTransactionsSQL::ERREUR,
			$message
		);
		$this->heliosTransactionsSQL->setAcquitFilename($helios_transaction_id, $basename);

        $this->s2lowLogger->info($message);
	}

	/**
	 * @param $basename
	 * @param SimpleXMLElement $xml
	 * @throws Exception
	 */
	private function traitementAck($basename,SimpleXMLElement $xml){
		$helios_transaction_id = $this->retrieveTransaction($xml);

		$this->s2lowLogger->info("Transaction trouvé : helios_transaction_id=$helios_transaction_id");

		if (count($xml->ACQUIT) == 0){
			$message = "Transaction $helios_transaction_id acceptee";
			$this->heliosTransactionsSQL->updateStatus($helios_transaction_id, HeliosTransactionsSQL::ACQUITTER, $message);

		} else {
			$message = "Transaction $helios_transaction_id : information disponible";
			$this->heliosTransactionsSQL->updateStatus($helios_transaction_id, HeliosTransactionsSQL::INFORMATION_DISPONIBLE, $message);
		}
		$this->s2lowLogger->info($message);
		$this->heliosTransactionsSQL->setAcquitFilename($helios_transaction_id, $basename);

	}

	/**
	 * @param SimpleXMLElement $xml
	 * @return mixed
	 * @throws Exception
	 */
	private function retrieveTransaction(SimpleXMLElement $xml,$verify_cod_col = true) {

		if (empty($xml->Enveloppe->Parametres->NomFic['V'])){
			throw new Exception("Impossible de trouver le NomFic dans le fichier");
		}

		$nom_fic = utf8_decode(strval($xml->Enveloppe->Parametres->NomFic['V']));
		$cod_col = strval($xml->EnTetePES->CodCol['V']);
		if (!$nom_fic){
			throw new Exception("Impossible de trouver l'attribut NomFic dans le PESAcquit");
		}
		if (!$cod_col){
            if ($verify_cod_col){
                throw new Exception("Impossible de trouver l'attribut CodCol dans le PESAcquit");
            } else {
                $helios_transaction_list = $this->heliosTransactionsSQL->getIdByNomFic($nom_fic);
            }
		} else {
            $helios_transaction_list = $this->heliosTransactionsSQL->getIdByNomFicAndCodCol($nom_fic, $cod_col);
        }
		
		if (!$helios_transaction_list){
			throw new Exception("L'identificant NomFic $nom_fic n'est associé à aucune transaction dans la base de données");	
		}

		if (count($helios_transaction_list) == 1){
			return $helios_transaction_list[0];
		}

		foreach($helios_transaction_list as $transaction_id){
			$workflow_info = $this->heliosTransactionsSQL->getLastStatusInfo($transaction_id);
			$transaction_list[] = array(
				'transaction_id' => $transaction_id,
				'status_id' => $workflow_info['status_id'],
				'date' => $workflow_info['date']
			);
		}

		usort($transaction_list,function ($a,$b){
            if (
				$a['status_id'] == HeliosTransactionsSQL::TRANSMIS &&
				$b['status_id'] != HeliosTransactionsSQL::TRANSMIS
			){
                return -1;
			}
            if (
                $b['status_id'] == HeliosTransactionsSQL::TRANSMIS &&
                $a['status_id'] != HeliosTransactionsSQL::TRANSMIS
            ){

                return 1;
            }
			if ($a['date'] > $b['date']){
				return -1;
			} else {
				return 1;
			}
		});
		return $transaction_list[0]['transaction_id'];
	}

	/**
	 * @param $basename
	 * @param SimpleXMLElement $xml
	 * @throws Exception
	 */
	private function traitementNack($basename,SimpleXMLElement $xml){
		$helios_transaction_id = $this->retrieveTransaction($xml);

		$this->s2lowLogger->info("Transaction trouvé : helios_transaction_id=$helios_transaction_id");

		$message = "Transaction $helios_transaction_id refusée";
		$this->heliosTransactionsSQL->updateStatus($helios_transaction_id, HeliosTransactionsSQL::REFUSER, $message,true);
		$this->heliosTransactionsSQL->setAcquitFilename($helios_transaction_id, $basename);

		$this->s2lowLogger->info($message);
	}

	/**
	 * @param $basename
	 * @param SimpleXMLElement $xml
	 * @throws Exception
	 */
	private function traitementPESRetour($basename,SimpleXMLElement $xml){
		$siret = strval($xml->EnTetePES->IdColl['V']);

		$authority_list = $this->authoritySiretSQL->authorityList($siret);
		if (! $authority_list){
			throw new Exception("La collectivité $siret n'est pas abonnée à l'application Comptabilité Publique du TdT, elle n'est donc pas autorisée à recevoir le PES_Retour ");
		}

		if (count($authority_list) > 1){
			throw new Exception("Le SIRET $siret est associé à plusieurs collectivités. Le PES_Retour n'est donc pas attribué");
		}
		$authority_id = $authority_list[0]['authority_id'];

		$this->heliosRetourSQL->add($authority_id, $siret, $basename);
	}
	
	private function sendMailToAdmin($subject,$msg){
		if (TESTING_ENVIRONNEMENT){
			return;
		}
		mail($this->email_admin,$subject,$msg,"from: {$this->email_from}");
	}
	
	
}