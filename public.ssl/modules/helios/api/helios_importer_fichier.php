<?php

/**
/*\file helios_importer_fichier.php
 * \brief Page permettannt l'import d'un fichier envoyé par POST HTTP et son "forward" vers le servlet
 *  renvois un fichier xml
 * \author HTAN
 * \date 16.12.2008
 */

// Configuration
require_once ("../../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');
require_once (SITEROOT . '/class/User.class.php');


$module = new Module();
if (!$module->initByName("helios")) {
  echo "Erreur d'initialisation du module";
  exit ();
}

// Instanciation du module courant
$me = new User();

//l'utilisateur'

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
//$signFile_baseName = $_FILES['signature']['name'];

$uploadfile = $uploaddir . basename($uploadFile_baseName);
//$signfile = $uploaddir . basename($signFile_baseName);

try{
	if (move_uploaded_file($_FILES['enveloppe']['tmp_name'], $uploadfile)) {
	
	
		//calculate the sha1 form the content of the file.
		$SHA1=sha1_file($uploadfile);
		
	  //move_uploaded_file($_FILES['signature']['tmp_name'], $signfile);
	  $ht = new HeliosTransaction();
	  $htw = new HeliosTransactionWorkflow();
	    //insertion (idUSer, filename, signed) dans la table helios_transactions  => un id de la transaction
	  // où filename = le nom du fichier inclut dans le fichier message 
	  //OBS : la valeur de l'id est automatiquement enregistrée par save() (voir DataObjet)
		$file_size=$_FILES['enveloppe']['size'];
	  $submission_date=date("Y-m-d H:i:s");;
	  $ht->set("filename", $uploadFile_baseName);
	  $ht->set("user_id", $userId);
	  $ht->set("file_size",$file_size);
	  $ht->set("submission_date",$submission_date);
	  $ht->set("sha1",$SHA1);
	  $myAuthority = new Authority($me->get("authority_id"));
	  $siren=$myAuthority->get('siren');
	  $ht->set("siren",$siren);
	  
	 	if ($ht->CheckDuplicate()== true)
	 	{
	 		unlink($uploadfile);
	  	$errorMsg="doublon détecté. Ce fichier a déjà été posté";
		  throw new Exception('KO');
	 	}
	  //change the upload file name to sha1 to allow duplicate name.
	  rename($uploadfile,$uploaddir.$SHA1);  
		chmod($uploaddir.$SHA1, 0644);
	
	  $R = $ht->save(true);
	  if (!$R) {
	    $msg= "Erreur de l'initialisaton de l'accès à la table helios_transactions.";
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
}
catch (Exception $e) {
       $resultatElement->appendChild( $doc->createTextNode( "KO" ) );
}
  $messageElement->appendChild( $doc->createTextNode( $msg));	
  $doc->save($xmlFile); 
if (!Helpers::sendFileToBrowser($xmlFile, basename($xmlFile), "text/xml")) {
	echo "error: impossible de envoyer ce xml "; 
}