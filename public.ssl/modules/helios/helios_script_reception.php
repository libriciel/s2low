<?php
require_once ("../../../init/init.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');
require_once (SITEROOT . '/class/User.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/antivirus.class.php');

$module = new Module();
if (!$module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();

if (!$me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

$nomUSer = $me->get("name");
$userId = $me->getId();

if (!$module->isActive() || !$me->checkDroit($module->get("name"),'CS')) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}


$ok = 0;
$ko = 0;

$uploaddir = HELIOS_FILES_UPLOAD_ROOT;
$uploadFile_baseName = $_FILES['enveloppe']['name'];
$uploadfile = $uploaddir . basename($uploadFile_baseName);

if (! move_uploaded_file($_FILES['enveloppe']['tmp_name'], $uploadfile)) {
	Helpers :: returnAndExit(1, "Échec lors du téléchargement du fichier", WEBSITE_SSL . "/modules/helios/helios_fichier_import.php");
}
	
if (!Antivirus::checkArchiveSanity($uploadfile)) {
	$_SESSION["error"] = Antivirus::$errorMsg;
	header("Location: " . WEBSITE_SSL);
	exit;
}

$SHA1=sha1_file($uploadfile);
	
$ht = new HeliosTransaction();
$htw = new HeliosTransactionWorkflow();
$file_size=$_FILES['enveloppe']['size'];
	
if ($file_size>HELIOS_MAX_UPLOAD_SIZE) {
	$_SESSION["error"] = "Taille de fichier supérieur à la limite autorisée (". (HELIOS_MAX_UPLOAD_SIZE/1024/1024)."Mo maximum).";
	header("Location: " . WEBSITE_SSL);
	exit ();
}
	
$submission_date=date("Y-m-d H:i:s");;
$ht->set("filename", $uploadFile_baseName);
$ht->set("user_id", $userId);
$ht->set("authority_id",$me->get("authority_id"));
$ht->set("file_size",$file_size);
$ht->set("submission_date",$submission_date);
$ht->set("sha1",$SHA1);

$myAuthority = new Authority($me->get("authority_id"));
$siren=$myAuthority->get('siren');
  
//ajouter le ext_siret(5 lettre) dans le nouveau siren.Il faut le renommer comme siret, mais ca va influence du côté java,
// car il récupère le numéro siren pour former le nom de fichier PES_Aller , donc je le garde mais le sens de SIREN est changé.
$ext_siret=$myAuthority->get('ext_siret');
$ht->set("siren",$siren.$ext_siret);
  
if ($ht->CheckDuplicate()== true) {
	unlink($uploadfile);
	Helpers :: returnAndExit(1, "doublon détecté. Ce fichier a déjà été posté.", WEBSITE_SSL . "/modules/helios/index.php");
}

//change the upload file name to sha1 to allow duplicate name.
rename($uploadfile,$uploaddir.$SHA1);  
chmod($uploaddir.$SHA1, 0644);

$R = $ht->save(true);

if (!$R) {
    $_SESSION["error"] = "Erreur de l'initialisaton de l'accès à la table helios_transactions.";
    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
		$_SESSION["error"] .= "\nErreur de journalisation.";
    }
    header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
    exit ();
}
  
$must_signed = Helpers::getVarFromPost("must_signed",true);

//recuperation de l'id de la transaction
$id_transaction = $ht->getId();

$htw->set("transaction_id", $id_transaction);
if ($must_signed){
	$htw->set("status_id", 13);
	$htw->set("message", "Fichier en attente d'être signé");
} elseif(! $me->checkDroit($module->get("name"),'TT')) {
	$htw->set("status_id", 14);
	$htw->set("message", "Fichier en attente d'être télétransmis");
	
} else {
	$htw->set("status_id", 1);
	$htw->set("message", "Fichier bien reçu par la plate-forme S2low");
}
$htw->set("date", date('Y-m-d H:i:s'));

if (!$htw->save(true)) {
	$_SESSION["error"] = "Erreur de l'initialisaton de l'accès à la table helios_transactions_workflow.";
    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
      $_SESSION["error"] .= "\nErreur de journalisation.";
    }
    header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
    echo $_SESSION["error"];
    exit ();
}


$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$heliosTransactionSQL->setLastStatusId($id_transaction);

$msg = "Création de la transation n°" . $id_transaction . ". Résultat ok.";
if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
	$msg .= "\nErreur de journalisation.";
}
    
Helpers :: returnAndExit(0,"Téléchargement du fichier réussi.", WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id_transaction);