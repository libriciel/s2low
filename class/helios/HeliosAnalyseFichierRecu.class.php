<?php

class HeliosAnalyseFichierRecu {
	
	const ID = "Helios Analyse Fichier Réception";
	
	private $heliosTransactionsSQL;
	private $authoritySQL;
	private $heliosRetourSQL;
	private $authoritySiretSQL;
	private $schema_pes_path;
	private $email_admin;
	private $email_from;

	public function __construct(
		HeliosTransactionsSQL $heliosTransactionsSQL,
		AuthoritySQL $authoritySQL,
		HeliosRetourSQL $heliosRetourSQL,
		AuthoritySiretSQL $authoritySiretSQL,$schema_pes_path,
		$email_admin,
		$email_from
	){
		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
		$this->authoritySQL = $authoritySQL;
		$this->heliosRetourSQL = $heliosRetourSQL;
		$this->schema_pes_path = $schema_pes_path;
		$this->email_admin = $email_admin;
		$this->authoritySiretSQL = $authoritySiretSQL;
		$this->email_from = $email_from;
	}
	
	public function analyse($helios_ftp_response_tmp_local_path, $helios_response_root,$helios_responses_error_path,$ocre_file_path){
		$helios_ftp_response_tmp_local_path = rtrim($helios_ftp_response_tmp_local_path,"/")."/";

		$this->log("Analyse du répertoire : $helios_ftp_response_tmp_local_path");

		$file_list = scandir($helios_ftp_response_tmp_local_path);

		if ($file_list === false){
			$this->log("[ECHEC] Erreur lors de la lecture du répertoire  $helios_ftp_response_tmp_local_path");
			return;
		}
		;
		$file_list = array_diff($file_list, array('..', '.'));

		if (!$file_list){
			$this->log("Aucun fichier à analyser");
			return;
		}
		$this->log("Traitement de ".count($file_list)." fichiers trouvés");

		$erreur_list = array();
		foreach($file_list as $file){
			try {
				$this->analyseOneFile($helios_ftp_response_tmp_local_path.$file,$helios_response_root,$ocre_file_path);
			} catch (Exception $e){
				$this->log("[ERREUR] ". $e->getMessage());
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
				$this->log("[ERREUR] Impossible de déplacer le fichier $file dans le répertoire des fichiers en erreur : le fichier existe déjà");
				$this->sendMailToAdmin("[S2low][Helios] Impossible de déplacer un fichier dans le répertoire des fichiers en erreur","Le fichier $file existe déjà");
				continue;
			}
			rename($helios_ftp_response_tmp_local_path."/".$file,$helios_responses_error_path."/".$file);
		}
	}
	
	private function log($message){
		echo utf8_encode(date("Y-m-d H:i:s")." [".self::ID."] $message\n");
	}
	
	public function analyseOneFile($file_path,$helios_response_root,$ocre_file_path,$validate_xsd = true){
		$basename = basename($file_path);
		$this->log("Traitement de $file_path");

		if (preg_match("#.ocre$#",strtolower($basename))){
			if (! rename($file_path,$ocre_file_path."/".$basename)){
				throw new Exception(" Le fichier $file_path n'a pas pu être déplacé !");
			}
			return;
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
			print_r($errors);
			throw new Exception("Le fichier $basename n'est pas valide (fichier ignoré)");
		}

		switch($root_name){
			case 'pes_acquit': $this->traitementAck($basename,$xml); break;
			case 'pes_nonacquit': $this->traitementNack($basename,$xml); break;
			case 'pes_retour' : $this->traitementPESRetour($basename,$xml); break;
			default: throw new Exception("$basename : Type PES retour inconnu : $root_name (fichier ignoré)");
		}

		if (! rename($file_path,$helios_response_root."/".$basename)){
			throw new Exception(" Le fichier $file_path n'a pas pu être déplacé !");
		}
	}
	
	private function traitementAck($basename,SimpleXMLElement $xml){
		$helios_transaction_id = $this->retrieveTransaction($xml);

		$this->log("Transaction trouvé : helios_transaction_id=$helios_transaction_id");

		if (count($xml->ACQUIT) == 0){
			$message = "Transaction $helios_transaction_id acceptee";
			$this->heliosTransactionsSQL->updateStatus($helios_transaction_id, HeliosTransactionsSQL::ACQUITTER, $message);
		} else {
			$message = "Transaction $helios_transaction_id : information disponible";
			$this->heliosTransactionsSQL->updateStatus($helios_transaction_id, HeliosTransactionsSQL::INFORMATION_DISPONIBLE, $message);
		}
		$this->log($message);
		$this->heliosTransactionsSQL->setAcquitFilename($helios_transaction_id, $basename);

	}
	
	private function retrieveTransaction(SimpleXMLElement $xml) {
		$nom_fic = utf8_decode(strval($xml->Enveloppe->Parametres->NomFic['V']));
		if (!$nom_fic){
			throw new Exception("Impossible de trouver l'attribut NomFic dans le PESAcquit");
		}
		$helios_transaction_id = $this->heliosTransactionsSQL->getIdByNomFic($nom_fic);
		
		if (!$helios_transaction_id){
			throw new Exception("L'identificant NomFic $nom_fic n'est associé à aucune transaction dans la base de données");	
		}
		return $helios_transaction_id;		
	}
	
	private function traitementNack($basename,SimpleXMLElement $xml){
		$helios_transaction_id = $this->retrieveTransaction($xml);

		$this->log("Transaction trouvé : helios_transaction_id=$helios_transaction_id");

		$message = "Transaction $helios_transaction_id refusée";
		$this->heliosTransactionsSQL->updateStatus($helios_transaction_id, HeliosTransactionsSQL::REFUSER, $message);

		$this->log($message);
		$this->heliosTransactionsSQL->setAcquitFilename($helios_transaction_id, $basename);
	}
	
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
		mail($this->email_admin,$subject,$msg,"from: {$this->email_from}");
	}
	
	
}