<?php 

class PEC_Reception {
	
	private $authoritySQL;
	private $userSQL;
	private $transactionDIA;
	private $fileDIA;
	
	public function __construct(AuthoritySQL $authoritySQL,UserSQL $userSQL,TransactionDIA $transactionDIA,FileDIA $fileDIA){
		$this->authoritySQL = $authoritySQL;
		$this->userSQL = $userSQL;
		$this->transactionDIA = $transactionDIA;
		$this->fileDIA = $fileDIA;
	}
	
	public function go($delivery_path){
		$this->retrieveDIAFromDeliveryFolder($delivery_path);
		$this->generateAndSendAE();
	}
	
	public function retrieveDIAFromDeliveryFolder($delivery_path){
		$handle = opendir($delivery_path);
		if (!$handle){
			echo "[ERREUR] Le répertoire $delivery_path n'existe pas ou n'est pas accessible !\n";
			return false;
		}
		while (false !== ($entry = readdir($handle))) {
			if ($entry == '.' || $entry == '..'){
				continue;
			}
        	$this->retrieveDIA($delivery_path."/".$entry);
    	}
	}
	
	public function retrieveDIA($entry){
		if (is_file($entry."/.locked")){
			echo "[IGNORER][$entry] Le répertoire contient un fichier .locked\n";
			return false;
		}
		
		if (! is_dir($entry."/attachment/")){
			echo "[IGNORER][$entry] Le répertoire ne contient pas de sous-répertoire -- attachment --\n";
			return false;
		}
		if (! is_file($entry."/attachment/message.xml")){
			echo "[IGNORER][$entry] Le répertoire attachment ne contient pas le fichier message.xml\n";
			return false;
		}
		$message = $entry."/attachment/message.xml";
		$dia_array = glob($entry."/attachment/DIA_DDI*.xml");
		if (! $dia_array){
			echo "[IGNORER][$entry] Le répertoire attachment ne contient pas de fichier DIA_DDI*.xml\n";
			return false;
		}
		if (count($dia_array) > 1){
			echo "[IGNORER][$entry] Le répertoire attachment contient plus d'un fichier DIA_DDI*.xml\n";
			return false;
		}
		$dia = $dia_array[0];
		
		$message_xml_content = file_get_contents($message);

		$message_xml = simplexml_load_file($message);
		 
		$namespaces = $message_xml->getNameSpaces(true);
		$pec = $message_xml->children($namespaces['pec']);
		
		$siret = strval($pec->Header->Routing->Recipients->Recipient->Location->Siret);
		$message_id = strval($pec->Header->Routing->MessageId);
		
		if ( ! $siret ){
			echo "[IGNORER][$entry] Le fichier attachment/message.xml ne contient pas de SIRET destinataire\n";
			return false;
		}
		
		$authority_id = $this->authoritySQL->getBySIRET($siret);
		if (!$authority_id){
			echo "[IGNORER][$entry] Le SIRET $siret contenu dans message.xml n'existe pas sur cette plateforme S²low\n";
			return false;
		}

		$all_user_id = $this->userSQL->getDIAUser($authority_id);
		if (!$all_user_id){
			echo "[IGNORER][$entry] La collectivité $authority_id n'a pas d'utilisateur pouvant lire et écrire dans le module DIA\n";
			return false;
		}
		$user_id = $all_user_id[0]['user_id'];
		if (count($all_user_id)> 1){
			echo "[WARING][$entry] La collectivité $authority_id contient plusieurs utilisateurs pouvant lire et écrire dans le module DIA. Choix de l'utilisateur $user_id\n";
		}
		
		if ($this->transactionDIA->messageExists($message_id)){
			echo "[IGNORER][$entry] Le message numéro $message_id existe déjà\n";
			return false;
		}
		
		$filename = basename($dia);
		$filesize = filesize($dia);

		$dia_id = $this->transactionDIA->createDIA($user_id, $filename, $filesize,$message_id,$message_xml_content);
		if (!$dia_id){
			echo "[IGNORER][$entry] Impossible de créer la DIA dans la base de données\n";
			return false;
		}
		
		$this->fileDIA->saveDIA($dia,$dia_id);
		
		$msg = "DIA numéro $message_id importée avec l'id S²low $dia_id";
		Log :: newEntry('Script import DIA', $msg, 1, false, 'USER', 'dia', false,$user_id);				
		echo "[OK][$entry] $msg\n";

		$tmp_folder = new TmpFolder();
		$tmp_folder->delete($entry);
		return true;
	}
		
		
		
	
	
	public function generateAndSendAE(){
		//pour chaque message
		//Créer l'AE
		//Signer l'AE
		//Créer le répertoire afin d'envoyer l'AE
		
	}
	
	
}