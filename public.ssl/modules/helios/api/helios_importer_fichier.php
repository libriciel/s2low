<?php
require_once ("../../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');
require_once (SITEROOT . '/class/User.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/antivirus.class.php');


$module = new Module();
if (!$module->initByName("helios")) {
  echo "Erreur d'initialisation du module";
  exit ();
}

$me = new User();
if (! $me->authenticate()) {
  echo "KO\nÉchec de l'authentification";
  exit();
}

$nomUSer = $me->get("name");
$userId = $me->getId();

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
  echo "KO\nAccès refusé";
  exit();
}

$doc = new DOMDocument();
$doc->formatOutput = true;	
$doc->preserveWhiteSpace = false;
$root=$doc->createElement("import");
	$doc->appendChild($root);
	$idElement=$doc->createElement("id");
	$resultatElement=$doc->createElement("resultat");
	$messageElement=$doc->createElement("message");
	
	$root->appendChild($idElement);
	$root->appendChild($resultatElement);
	$root->appendChild($messageElement);
	$xmlFile=HELIOS_FILES_ROOT."/temp/import.xml";
	$ok = 0;
	$ko = 0;

$uploaddir = HELIOS_FILES_UPLOAD_ROOT;

$uploadFile_baseName = $_FILES['enveloppe']['name'];

$uploadfile = $uploaddir . basename($uploadFile_baseName);

try{
	if (move_uploaded_file($_FILES['enveloppe']['tmp_name'], $uploadfile)) {

		if (!Antivirus::checkArchiveSanity($uploadfile)) {
			$msg = Antivirus::$errorMsg;
			throw new Exception('KO');			
		}
		
		$SHA1=sha1_file($uploadfile);
		
	 
	$file_size=$_FILES['enveloppe']['size'];
	
	if ($file_size>HELIOS_MAX_UPLOAD_SIZE) {
		$msg = "Taille de fichier supérieure à la limite autorisée (". (HELIOS_MAX_UPLOAD_SIZE/1024/1024)."Mo maximum).";
		throw new Exception('KO');
	}
	
	$ht = new HeliosTransaction();
	$htw = new HeliosTransactionWorkflow();
	  $submission_date=date("Y-m-d H:i:s");;
	  $ht->set("filename", $uploadFile_baseName);
	  $ht->set("user_id", $userId);
	  $ht->set("file_size",$file_size);
	  $ht->set("submission_date",$submission_date);
	  $ht->set("sha1",$SHA1);
	  $ht->set("authority_id",$me->get("authority_id"));
	  $ht->set("last_status_id",1);
	  
	  $myAuthority = new Authority($me->get("authority_id"));
	  $siren=$myAuthority->get('siren');
	  $ht->set("siren",$siren);
	  
	 	if ($ht->CheckDuplicate()== true) {
	 		unlink($uploadfile);
	  		$msg="doublon détecté. Ce fichier a déjà été posté";
		  	throw new Exception('KO');
	 	}
	  rename($uploadfile,$uploaddir.$SHA1);  
		chmod($uploaddir.$SHA1, 0644);
	
	  $R = $ht->save(true);
	  if (!$R) {
	    $msg= "Erreur de l'initialisaton de l'accès à la table helios_transactions : " . $ht->getErrorMsg();
	    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
	      $msg .= "\nErreur de journalisation.";
	    }
		  throw new Exception('KO');
	  }
	  //recuperation de l'id de la transaction
	  $id_transaction = $ht->getId();
	  $idElement->appendChild( $doc->createTextNode($id_transaction));
	  $htw->set("transaction_id", $id_transaction);
	  $htw->set("status_id", 1);
	  $htw->set("date", date('Y-m-d H:i:s'));
	  $htw->set("message", "Fichier bien reçu par la plate-forme Helios");
	  if (!$htw->save(true)) {
	    $msg = "Erreur de l'initialisaton de l'accès à la table helios_transactions_workflow.";
	    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
	      $msg .= "\nErreur de journalisation.";
	    }
	  	throw new Exception('KO');
	  } 
    $msg = "Téléchargement du fichier réussi.";
    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
      $msg .= "\nErreur de journalisation.";
    }				
   	$resultatElement->appendChild( $doc->createTextNode("OK"));
	}
	else
	{
	    $msg="Echec lors du téléchargement du fichier";
	    throw new Exception('KO'); 
	}
} catch (Exception $e) {
	$resultatElement->appendChild( $doc->createTextNode( "KO" ) );
}

$messageElement->appendChild( $doc->createTextNode( utf8_encode($msg)));	
$doc->save($xmlFile); 

if (!Helpers::sendFileToBrowser($xmlFile, basename($xmlFile), "text/xml")) {
	echo "KO impossible d'envoyer le fichier XML"; 
}