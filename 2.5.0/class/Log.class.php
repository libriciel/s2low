<?php


require_once(SITEROOT . "/class/DataObject.class.php");
require_once(SITEROOT . "/class/Parapheur.class.php");

class Log extends DataObject {
  protected $objectName = "logs";
  protected $prettyName = "Entrée de journal";

  protected $date;
  protected $module;
  protected $severity;
  protected $issuer;
  protected $user_id;
  protected $visibility;
  protected $message;
  protected $timestamp;

  protected $dbFields = array( "date" => array( "descr" => "Date", "type" => "isDate", "mandatory" => true),
						 "module" => array( "descr" => "Module", "type" => "isString", "mandatory" => false),
						 "severity" => array( "descr" => "Sévérité", "type" => "isString", "mandatory" => true),
						 "issuer" => array( "descr" => "Émetteur de l'entrée", "type" => "isString", "mandatory" => false),
						 "user_id" => array( "descr" => "Identifiant de l'utilisateur", "type" => "isInt", "mandatory" => false),
						 "visibility" => array( "descr" => "Visibilité", "type" => "isString", "mandatory" => false),
						 "message" => array( "descr" => "Message", "type" => "isString", "mandatory" => true),
						 "timestamp" => array( "descr" => "Horodatage", "type" => "isString", "mandatory" => false),
	  						"authority_id" => array("descr"=>"Authority","type"=>"isInt","mandatory"=>false),
	  "authority_group_id" => array("descr"=>"Authority group","type"=>"isInt","mandatory"=>false),
						 );

  protected $severities = array( 0 => "DEBUG",
						   1 => "INFO",
						   2 => "WARNING",
						   3 => "ERROR",
						   4 => "CRITICAL"
						   );

  /**
   * \brief Constructeur d'une entrée de log
   * \param id integer Numéro d'id d'une collectivité existante avec lequel initialiser l'objet
   */
  public function __construct($id = false) {
    parent::__construct($id);
	if ($id) {
	  $this->init();
	}
  }

  /**
   * \brief Constructeur d'une entrée de log à partir d'infos fournies en paramètres
   * \param $issuer chaîne : Créateur de l'entrée de journal
   * \param $message chaîne : Message de l'entrée de journal
   * \param $severity chaîne : Sévérité du message
   * \param $date chaîne (optionnel) : Date de l'entrée de journal (date courante par défaut)
   * \param $visibility chaîne (optionnel) : Visibilité de l'entrée de log ('USER', 'ADM' ou 'SADM') (vide par défaut)
   * \param $module chaîne (optionnel) : Module concerné par le message (vide par défaut)
   * \param $user objet User (optionnel) : Utilisateur concerné par l'entrée de journal (vide par défaut)
   * \return True en cas de succès, false sinon
   */
  public static function newEntry($issuer, $message, $severity, $date = false, $visibility = false, $module = false, $user = false ,$userid=false) {
	$logEntry = new Log();
	$logEntry->set("issuer", $issuer);
	$logEntry->set("message", $message);
	$logEntry->set("severity", $severity);
	if (! $date) {
	  $date = date('Y-m-d H:i:s');
	}

	$logEntry->set("date", $date);

	if ($module) {
	  $logEntry->set("module", $module);
	}


	if ($user) {
		$userid = $user->getId();
	}

	if ($userid) {
		$logEntry->set("user_id",$userid);
	}
	if ($visibility) {
	  $logEntry->set("visibility", $visibility);
	}

	  global $sqlQuery;
	  $authority_id = false;
	  $authority_group_id = false;
	  if ($userid) {
		  $userSQL = new UserSQL($sqlQuery);
		  $info = $userSQL->getInfo($userid);
		  if ($info) {
			  $authority_id = $info['authority_id'];

		  }
	  }
	  if ($authority_id){
		$authoritySQL = new AuthoritySQL($sqlQuery);
		$info = $authoritySQL->getInfo($authority_id);
		  if ($info){
			  $authority_group_id = $info['authority_group_id'];
		  }
	  }

	  $logEntry->set("authority_id",$authority_id);
	  $logEntry->set("authority_group_id",$authority_group_id);


	// Enregistrement de l'entrée pour déterminer son id
	if (! $logEntry->save()) {
	  return false;
	}

	if (! $timestamp = $logEntry->genTimestamp()) {
	  return false;
	}

	$logEntry->set("timestamp", $timestamp);

	if (! $logEntry->save()) {
	  return false;
	}

	return true;
  }

  /**
   * \brief Méthode de détermination de l'horodatage d'une ligne de journal
   * \return La chaîne correspondant à l'horodatage de l'entrée, false sinon
   */
  public function genTimestamp() {
	  if (TESTING_ENVIRONNEMENT){
		  //FIXME
		return "TESTING";
	  }
	// on crée un fichier contenant la concaténation de tous les champs de l'entrée
	$logFile = tempnam('/tmp', 'tedetis_web_');

	$data = $this->getConcatLog();

	if (! $this->writeLogEntryToFile($logFile, $data)) {
	  return false;
	}

	$parapheur = new Parapheur($data);
	$signature = $parapheur->getSignature();
	
	if (! $signature){
		$this->errorMsg = $parapheur->getLastError();
		return false;
	}

	  unlink($logFile);
	
	return $signature;
  } 

  /**
   * \brief Méthode qui détermine si un utilisateur a la permission de visualiser l'entrée de journal courante
   * \param $user User : Objet utilisateur concerné
   */
  public function canView($user) {
	if ($user->isSuper()) {
	  return true;
	}

	if (! empty($this->user_id)) {
	  // L'utilisateur est "propriétaire" de l'entrée
	  if ($this->user_id == $user->getId()) {
		if ($user->isAdmin()) {
		  // Un admin peut voir tout ce qui n'est pas en visibilité SADM
		  if ($this->visibility != 'SADM') {
			return true;
		  }
		} else {
		  // Un utilisateur peut voir tout ce qui n'est pas en visibilité 'ADM', 'GADM' et 'SADM'
		  if ($this->visibility != 'SADM' && $this->visibility != 'ADM' && $this->visibility != 'GADM') {
			return true;
		  }
		}
	  } else {
		// Utilisateur non propriétaire de l'entrée
		// Si l'utilisateur est admin de collectivité, il peut voir l'entrée si elle appartient à un utilisateur de sa collectivité
		$owner = new User($this->user_id);
		$owner->init();

		$hisAuthority = new Authority($owner->get("authority_id"));

		if (($user->isGroupAdmin() && $hisAuthority->isInGroup($user->get("authority_group_id"))) || ($user->isAuthorityAdmin() && $user->get("authority_id") == $owner->get("authority_id"))) {
		  return true;
		}
	  }
	} else {
	  // Entrée non associé à un utilisateur
	  if ($user->isAuthorityAdmin() && ($this->visibility == 'ADM' || $this->visibility == 'USER')) {
		return true;
	  }

	  if ($user->isGroupAdmin() && ($this->visibility == 'GADM' || $this->visibility == 'ADM' || $this->visibility == 'USER')) {
		return true;
	  }

	  if (! $user->isAdmin() && $this->visibility == 'USER') {
		return true;
	  }
	}

	return false;
  }

  /**
   * \brief Méthode de génération et d'envoi d'une archive contenant le fichier de l'entrée de log et son horodatage
   * \return True en cas succès, false sinon
   */
  public function sendArchive() {
	if (isset($this->id) && ! empty($this->id)) {
	  $tmpDir = '/tmp/tedetis_web_export_timestamp';

	  for ($i = 0; $i <= 8; $i++) {
		$tmpDir .= rand(0, 9);
	  }

	  if (! @mkdir($tmpDir)) {
		$this->errorMsg = "Erreur système de fichiers";
		return false;
	  }

	  $logFile = $tmpDir . "/tedetis_journal_" . $this->id . ".log";
	  $timestampFile = $logFile . ".sig";

	  if (! $this->writeLogEntryToFile($logFile, $this->getConcatLog())) {
		return false;
	  }

	  if (! $this->writeTimestampToFile($timestampFile)) {
		return false;
	  }

	  if (! @chdir($tmpDir)) {
		$this->errorMsg =  "Erreur système de fichiers";
		return false;
	  }

	  // Génération de l'archive zip
	  $zipFile = "tedetis_journal_" . $this->id . ".zip";
	  $cmd = "/usr/bin/zip -9 " . $zipFile . " " . basename($logFile) . " " . basename($timestampFile);

	  exec($cmd, $out, $ret);

	  if ($ret != 0) {
		$return_status = false;
	  } else {
		// Envoi du fichier
		header("Content-type: application/zip");
		header('Content-disposition: attachment; filename="' . $zipFile . '"');
		// Celles-ci pour IE
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");

		if (! @readfile($tmpDir . "/" . $zipFile)) {
		  $this->errorMsg =  "Erreur d'envoi du fichier archive.";
		  $return_status = false;
		} else {
		  $return_status = true;
		}

		if (! Helpers::deleteFromFS($zipFile)) {
		  $this->errorMsg = "Erreur système de fichiers";
		  return false;
		}
	  }

	  // Suprression des fichiers temporaires
	  // Bien laissé le répertoire à la fin
	  if (! Helpers::deleteFromFS($logFile, $timestampFile, $tmpDir)) {
		$this->errorMsg =  "Erreur système de fichiers";
		return false;
	  }

	  return $return_status;
	}

	return false;
  }

  /**
   * \brief Méthode d'écriture de l'entrée de journal dans un fichier
   * \return True en cas de succès, false sinon
   */
  public function writeLogEntryToFile($logFile, $data) {
	if (! file_put_contents($logFile, $data)) {
	  $this->errorMsg = "Erreur système de fichiers.";
	  return false;
	}

	return true;
  }

  /**
   * \brief Méthode d'obtention de l'entrée de log en format concaténé pour horodatage
   * \return La chaîne de tous les champs séparés par '**||**'
   */
  public function getConcatLog() {
	$data[] = $this->id;
	$data[] = date('Y-m-d H:i:s', Helpers::getTimestampFromBDDDate($this->date));
	$data[] = $this->module;
	$data[] = $this->severity;
	$data[] = $this->issuer;
	$data[] = $this->user_id;
	$data[] = $this->visibility;
	$data[] = $this->message;


	$log = implode($data, "**||**");

	return $log;
  }

  /**
   * \brief Méthode d'écriture de l'horodatage dans un fichier
   * \return True en cas de succès, false sinon
   */
  function writeTimestampToFile($timestampFile) {
	if (isset($this->timestamp) && ! empty($this->timestamp)) {
	  if (! file_put_contents($timestampFile, $this->timestamp)) {
		$this->errorMsg = "Erreur système de fichiers.";
		return false;
	  }

	  return true;
	}

	return false;
  }

  /**********************/
  /* Méthodes statiques */
  /**********************/

  /**
   * \brief Méthode d'obtention d'une liste d'entrées de journal
   * \param $cond (optionnel) chaîne Chaîne contenant les conditions (SQL) à appliquer à la fin de la requête BDD
   * \return Tableau des entrées de journal
  */
  public function getLogEntriesList($cond = "") {
	if (! $this->pagerInit('logs.id, logs.date, logs.module, logs.severity, logs.issuer, logs.user_id, logs.message, logs.timestamp', 'logs LEFT JOIN users ON logs.user_id=users.id LEFT JOIN authorities ON users.authority_id=authorities.id', $cond)) {
	  return false;
	}

    return $this->data;
  }
}

