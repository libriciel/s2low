<?php 

require_once("ActesEnvelope.class.php");
require_once("ActesClassification.class.php");
require_once( SITEROOT . "class/Module.class.php");
require_once( SITEROOT . "class/User.class.php");


class ActesClassificationCreation {
	
	private $authority;
	private $user;
	
	private $frequencyRestriction = true;
	
	private $lastMessage;
	private $lastTransactionId;
	
	private $db;
	
	public function __construct($db = null){
		if (!$db){
			$db = DatabasePool::getInstance();
		}
		$this->db = $db;
	}
	
	public function unsetFrequencyRestriction(){
		$this->frequencyRestriction = false;
	}
	
	public function getLastMessage(){
		return $this->lastMessage;
	}
	
	public function getLastTransactionId(){
		return $this->lastTransactionId;
	}
	
	public function createEnveloppe(Authority $authority,User $user = null){	
		$this->authority = $authority;
		$this->user = $user;
		if (! $user){
			$result = $this->setDefaultUser();
			if (!$result){
				$this->lastMessage = "La collectivité ne contient pas d'utilisateur";
				return false;
			}
		} 
		
		if ($this->frequencyRestriction && ActesClassification::hasTodayRequest($this->authority->getId())){
			$this->lastMessage =  "La dernière demande de classification date de moins d'un jour.";
			return false;
		}
		
		$env = $this->initEnveloppe();
		$trans = $this->initTransaction();
		
		// Génération du fichier XML de la transaction
		$xml_name = $trans->getStdFileName($env, false);
		if (! $trans->generateMessageXMLFile($xml_name)) {
			$this->lastMessage = "Erreur lors de la génération du message métier :\n" . $trans->getErrorMsg();
			return false;
		}
		
		$env->addTransaction($trans);
		require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelopeSerialSQL.class.php');


		$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
		$serialNumber = $actesEnvelopeSerial->getNext($authority->getId());

		// Génération du fichier XML de l'enveloppe
		if (! $env->generateEnvelopeXMLFile($serialNumber)) {
			$this->lastMessage = "Erreur lors de la génération de l'enveloppe.";
			return false;
		}
		
		// Création de l'archive .tar.gz
		if (! $env->generateArchiveFile()) {
			$this->lastMessage = "Erreur lors de la génération de l'archive.\n" . $env->getErrorMsg();
			return false;
		}
		
		// Purge des fichiers intermédiaires
		$env->purgeFiles();
		$result = $env->save();
		if (! $result) {
			$this->lastMessage = "Erreur lors de l'enregistrement de l'enveloppe&nbsp;:\n" . $env->getErrorMsg();
			$this->logLastMessage(3);
			return false;
		}
		
		$trans->set("envelope_id", $env->getId());
		$result = $trans->save();
		if (! $result) {			
			$this->lastMessage  = "Erreur lors de l'enregistrement de la transaction&nbsp;:\n" . $trans->getErrorMsg();
			$this->logLastMessage(3);
			$env->deleteArchiveFile();
			$env->delete();
			return false;
		} 
		
		$classifRequest = new ActesClassification();
		$classifRequest->set("requested_by", $this->user->getId());
		$classifRequest->set("request_date", date('Y-m-d H:i:s'));
	
	  	if (! $classifRequest->save()) {
			$trans->delete();
			$env->deleteArchiveFile();
			$env->delete();
			$this->lastMessage = "Erreur lors de l'enregistrement de la requête de classification.\n" . $classifRequest->getErrorMsg();
			return false;
	  	}
		
		$this->lastMessage = "Création envelope n°" . $env->getId() . " contenant une demande de classification. Résultat OK.";
		$this->logLastMessage(1);

		$this->lastTransactionId = $trans->getId() . "\n";
		return true;
	}
	
	private function setDefaultUser(){
		$sql = "SELECT users.id " . 
				" FROM users " . 
				" JOIN users_perms ON users.id = users_perms.user_id " . 
				" WHERE authority_id=".$this->authority->getId().
				" AND status=1 " .
				" AND role='USER'" .
				" AND module_id =  ". Module::ACTES .
				" AND perm = '".User::PERM_MODIFICATION."' " .
				" ORDER BY users.id ASC " .
				" LIMIT 1 " ;
		$id_user = $this->db->getOneValue($sql);
		if (! $id_user){
			return false;
		}
		$this->user = new User($id_user);
		$this->user->init();
		return true;
	}
	
	private function getMailRetour(){
		$retMail = array(ACTES_TDT_MAIL_ADDRESS);

		if ($this->user->get("email")) {
  			$retMail[] = $this->user->get("email");
		}
		if ($this->authority->get("email")) {
  			$retMail[] = $this->authority->get("email");
		}
		return $retMail;
	}
	
	private function getTelephoneContact(){
		if ($this->user->get("telephone")) {
		  $telephone = $this->user->get("telephone");
		} else {
		  $telephone = $this->authority->get("telephone");
		}	
	}	
	
	private function initEnveloppe(){
		
		$mailRetour = $this->getMailRetour();
		$telephone = $this->getTelephoneContact();
	
		$env = new ActesEnvelope();
		
		// Initialisation de l'enveloppe
		$env->set("user_id", $this->user->getId());
		$env->set("siren", $this->authority->get("siren"));
		$env->set("department", $this->authority->get("department"));
		$env->set("district", $this->authority->get("district"));
		$env->set("authority_type_code", $this->authority->get("authority_type_id"));
		$env->set("return_mail", implode($mailRetour, '|'));
		$env->set("name", $this->user->getprettyName());
		$env->set("telephone", $telephone);
		$env->set("email", $this->user->get("email"));
		$env->set("file_path", "");
		$env->set("destDir", $this->authority->get("siren") . "/");
		return $env;
	}
	
	private function initTransaction(){
		$last_classification_date = ActesClassification::getLastRevisionDate($this->authority->getId());
		$trans = new ActesTransaction();
		$trans->set("type", "7");
		$trans->set("last_classification_date",$last_classification_date);
		$trans->set("destDir", $this->authority->get("siren") . "/" );
		$trans->set("authority_id",$this->authority->getId());
		$trans->set("user_id",$this->user->getId());
		return $trans;
	}
	
	private function logLastMessage($severity){
		$result = Log::newEntry(LOG_ISSUER_NAME, $this->lastMessage, $severity, false, 'USER', "actes", $this->user);
		if (! $result) {
		  $this->lastMessage .= "\nErreur de journalisation.";
		}
	}
}