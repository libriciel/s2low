<?php

class HeliosAnalyseFichierRecu {
	
	const ID = "Helios Analyse Fichier Réception";
	
	private $heliosTransactionsSQL;
	private $authoritySQL;
	private $heliosRetourSQL;
	private $schema_pes_path;
	
	public function __construct(HeliosTransactionsSQL $heliosTransactionsSQL, AuthoritySQL $authoritySQL, HeliosRetourSQL $heliosRetourSQL, $schema_pes_path){
		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
		$this->authoritySQL = $authoritySQL;
		$this->heliosRetourSQL = $heliosRetourSQL;
		$this->schema_pes_path = $schema_pes_path;
	}
	
	private function log($message){
		echo utf8_encode(date("Y-m-d H:i:s")." [".self::ID."] $message\n");
	}
	
	public function analyse($helios_ftp_response_tmp_local_path, $helios_response_root,$helios_responses_error_path){
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
				$this->analyseOneFile($helios_ftp_response_tmp_local_path.$file,$helios_response_root);
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
			mail(EMAIL_ADMIN,$subject,$msg);
		}
		
		foreach($erreur_list as $file => $message){
			if (file_exists($helios_responses_error_path."/".$file)){
				mail(EMAIL_ADMIN,"[S2low][Helios] Impossible de déplacer un fichier dans le répertoire des fichiers en erreur","Le fichier $file existe déjà");
				continue;
			}
			rename($helios_ftp_response_tmp_local_path."/".$file,$helios_responses_error_path."/".$file);
		}
		
	}
	
	private function analyseOneFile($file_path,$helios_response_root){
		$basename = basename($file_path);
		$this->log("Traitement de $file_path");
		
		libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->load($file_path);
		
		$errors = libxml_get_errors();
		libxml_clear_errors();
		
		if ($errors){
			throw new Exception("Le fichier $basename n'est pas bien formé (fichier ignoré)");
		}
		$dom->schemaValidate($this->schema_pes_path."/PES_V2/Rev0/PES_V2_Acquit_Autonome.xsd");
		$errors = libxml_get_errors();
		libxml_clear_errors();
		
		if ($errors){
			throw new Exception("Le fichier $basename n'est pas valide (fichier ignoré)");
		}
		
		$xml = simplexml_load_file($file_path);
		$root_name = strtolower($xml->getName());
		switch($root_name){
			case 'pes_acquit': $this->traitementAck($basename,$xml); break;
			case 'pes_nonacquit': $this->traitementNack($basename,$xml); break;
			case 'pes_retour' : $this->traitementRetour($basename,$xml); break;
			default: throw new Exception("$basename : Type PES retour inconnu : $root_name (fichier ignoré)");
		}
		
		if (! rename($file_path,$helios_response_root."/".$basename)){
			throw new Exception(" Le fichier $file_path n'a pas pu être déplacé !");
		}
		
		
	}
	
	
	
	private function retrieveTransaction(SimpleXMLElement $xml) {
		$nom_fic = strval($xml->Enveloppe->Parametres->NomFic['V']);
		if (!$nom_fic){
			throw new Exception("Impossible de trouver l'attribut NomFic dans le PESAcquit");
		}
		$helios_transaction_id = $this->heliosTransactionsSQL->getIdByNomFic($nom_fic);
		
		if (!$helios_transaction_id){
			throw new Exception("L'identificant NomFic $nom_fic n'est associé à aucune transaction dans la base de données");	
		}
		return $helios_transaction_id;		
	}
	
	private function traitementAck($basename,SimpleXMLElement $xml){
		$helios_transaction_id = $this->retrieveTransaction($xml);
		
		echo $this->log("Transaction trouvé : helios_transaction_id=$helios_transaction_id");
		
		if (count($xml->ACQUIT)){
			$message = "Transaction $helios_transaction_id acceptee";
			$this->heliosTransactionsSQL->updateStatus($helios_transaction_id, HeliosTransactionsSQL::ACQUITTER, $message);
		} else {
			$message = "Transaction $helios_transaction_id : information disponible";
			$this->heliosTransactionsSQL->updateStatus($helios_transaction_id, HeliosTransactionsSQL::INFORMATION_DISPONIBLE, $message);
		}
		echo $this->log($message);
		$this->heliosTransactionsSQL->setAcquitFilename($helios_transaction_id, $basename);
	
	}
	
	private function traitementNack($basename,SimpleXMLElement $xml){
		$helios_transaction_id = $this->retrieveTransaction($xml);
	
		echo $this->log("Transaction trouvé : helios_transaction_id=$helios_transaction_id");
	
		$message = "Transaction $helios_transaction_id refusée";
		$this->heliosTransactionsSQL->updateStatus($helios_transaction_id, HeliosTransactionsSQL::REFUSER, $message);
		
		echo $this->log($message);
		$this->heliosTransactionsSQL->setAcquitFilename($helios_transaction_id, $basename);
	}
	
	private function traitementPESRetour($basename,SimpleXMLElement $xml){
		$siren = strval($xml->EnTetePES->IdColl['V']);
		
		$authority_id = $this->authoritySQL->getIdBySIREN($siren);
		if (! $authority_id){
			throw new Exception("La collectivité "+siren+ " n'est pas abonnée à l'application Comptabilité Publique du TdT, elle n'est donc pas autorisée à recevoir le PES_Retour ");
		}
		
		$this->heliosRetourSQL->create($siren, $basename);
	}
	
	
}