<?php

$errorMsg = "";

function sortir($message,$api){
	global $related_id;
	if ($api){
    	echo "KO : " . $message;
    	exit;
    } else {
    	Helpers :: returnAndExit(1, $message, WEBSITE_SSL . "/modules/actes/actes_transac_repondre.php?id=$related_id");
    }
}

// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesClassification.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesBatch.class.php');

$api = Helpers::getVarFromGet("api");
if ($api){
	header("Content-type: text/plain");
}

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
	 sortir("Erreur d'initialisation du module",$api);
}

$me = new User();

if (!$me->authenticate()) {
	sortir( "Échec de l'authentification",$api);
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canEdit($module->get("name"))) {
	sortir( "Accès refusé",$api);
}

if ($module->getParam("paper") == "on") {
	sortir(  "Mode « papier » actif. Accès interdit.",$api);
}

$myAuthority = new Authority($me->get("authority_id"));

// Recuperation des variables du POST

$related_id = Helpers::getVarFromPost("id");


$related_trans = new ActesTransaction($related_id);
$related_trans->init();

$type_transaction = $related_trans->get("type");
$type_envoie = Helpers :: getVarFromPost("type_envoie", true);

$actePDFFile = $_FILES["acte_pdf_file"];
if (isset($_FILES["acte_pdf_file_sign"])){
	$actePDFFileSign = $_FILES["acte_pdf_file_sign"];
} else {
	$actePDFFileSign = false;
}

if (isset($_FILES["acte_attachments"])){
	$acteAttachments = $_FILES["acte_attachments"];
} else {
	$acteAttachments = false;
}

if (isset($_FILES["acte_attachments_sign"])){
	$acteAttachmentsSign = $_FILES["acte_attachments_sign"];
} else {
	$acteAttachmentsSign = false;
}

$env = new ActesEnvelope();
$trans = new ActesTransaction();

$retMail = array ();
$retMail[] = ACTES_TDT_MAIL_ADDRESS;

if ($me->get("email")) {
  $retMail[] = $me->get("email");
}
if ($myAuthority->get("email")) {
  $retMail[] = $myAuthority->get("email");
}

// Initialisation de l'enveloppe
$env->set("user_id", $me->getId());
$env->set("siren", $myAuthority->get("siren"));
$env->set("department", $myAuthority->get("department"));
$env->set("district", $myAuthority->get("district"));
$env->set("authority_type_code", $myAuthority->get("authority_type_id"));
$env->set("return_mail", implode($retMail, '|'));
$env->set("name", $me->getprettyName());
$env->set("telephone", "");
$env->set("email", $me->get("email"));
$env->set("file_path", "");

// Initialisation de la transaction
$trans->set("type", $related_trans->get("type"));
$trans->set("related_transaction", $related_trans);
$trans->set("related_transaction_id", $related_trans->get("id"));
$trans->set("nature_code", $related_trans->get("nature_code"));
$trans->set("nature_descr", $related_trans->get("nature_descr"));
$trans->set("subject", $related_trans->get("subject"));
$trans->set("number", $related_trans->get("number"));
$trans->set("decision_date", Helpers::getANSIDateFromBDDDate($related_trans->get("decision_date")));
$trans->set("type_reponse",$type_envoie);

// Destination de création des fichiers
$dest = $env->get("siren") . "/" . $trans->get("number") . "/";
$trans->set("destDir", $dest);
$env->set("destDir", $dest);

// On essaye d'importer tous les fichiers même si une erreur se produit
// lors de l'import de l'un d'eux. On trace les erreurs avec un booléen.
$fileImportError = false;

// Validation du type des fichiers uploadés
// Fichier de l'acte
if (isset ($actePDFFile) ) {

    if (is_uploaded_file($actePDFFile["tmp_name"])) {
      $acteFilePath = $actePDFFile["tmp_name"];
      $acteFileName = $actePDFFile["name"];
    } else {
 		 sortir( "Envoi de fichier illégal.",$api);    	
    }

  $dest_name = $trans->getStdFileName($env);
  if (!$trans->addActeFile($acteFileName, $dest_name, $acteFilePath)) {
    $errorMsg = "Erreur de validation du fichier de l'acte :\n" . $trans->getErrorMsg() . "\n";
    $fileImportError = true;
  } else {
    // Ajout de la signature si présente
    $signFile = null;

      if (isset ($actePDFFileSign["tmp_name"]) && is_uploaded_file($actePDFFileSign["tmp_name"])) {
        $signFile = $actePDFFileSign["tmp_name"];
        $readFile = true;
      }

    if ($signFile) {
      if (!$trans->addActeSign($signFile, $readFile)) {
        $errorMsg .= "Erreur lors du traitement de la signature du fichier " . $acteFileName . " :\n" . $trans->getErrorMsg() . "\n";
        $fileImportError = true;
      }
    }
  }
}


// Fichiers des pièces jointes
//if (! $batchMode) { // Pas de pièces jointes en mode lot
if (isset ($acteAttachments)) {
  for ($i = 0; $i < count($acteAttachments["tmp_name"]); $i++) {
    if (strlen($acteAttachments["tmp_name"][$i])) {
      if (is_uploaded_file($acteAttachments["tmp_name"][$i])) {
        // Sauvegarde dans la session pour réaffichage en cas d'erreur dans le formulaire
        // Désactivé, de toute façon on ne peut pas préremplir un champ de type file
        /*Helpers::putInSession("attachment_file" . ($i + 1), $acteAttachments["name"][$i]);
        if (isset($acteAttachmentsSign["tmp_name"][$i]) {
        Helpers::putInSession("attachment_sign_file" . ($i + 1), $acteAttachmentsSign["name"][$i]);
        }*/

        $dest_name = $trans->getStdFileName($env);
        if (!$trans->addAttachmentFile($acteAttachments["name"][$i], $dest_name, $acteAttachments["tmp_name"][$i])) {
          $errorMsg .= "Erreur de validation d'un fichier de pièce jointe :\n" . $trans->getErrorMsg() . "\n";
          $fileImportError = true;
        } else {
          // Ajout de la signature si présente
          if (isset ($acteAttachmentsSign["tmp_name"][$i]) && is_uploaded_file($acteAttachmentsSign["tmp_name"][$i])) {
            if (!$trans->addAttachmentSign($acteAttachmentsSign["tmp_name"][$i])) {
              $errorMsg .= "Erreur lors du traitement de la signature du fichier " . $acteAttachments["name"][$i] . " :\n" . $trans->getErrorMsg() . "\n";
              $fileImportError = true;
            }
          }
        }
      } else {
      	sortir(  "Envoi de fichier illégal.",$api);
      }
    }
  }
}
//}

// Vérification des signatures éventuelles des fichiers
if (!$trans->checkSign()) {
  $errorMsg .= "Erreur de vérification des signatures des fichiers : " . $trans->getErrorMsg() . "\n";
  $fileImportError = true;
}

if ($fileImportError) {
	sortir($errorMsg,$api);
}

// Génération du fichier XML de l'acte
$xml_name = $trans->getStdFileName($env, false);
if (!$trans->generateMessageXMLFile($xml_name)) {
	sortir("Erreur lors de la génération de l'acte : " . $trans->getErrorMsg(),$api);
}

$env->addTransaction($trans);

require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelopeSerialSQL.class.php');

$authority_id = $me->get("authority_id");

$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
$serialNumber = $actesEnvelopeSerial->getNext($authority_id);

// Génération du fichier XML de l'enveloppe
if (!$env->generateEnvelopeXMLFile($serialNumber)) {
	sortir("Erreur lors de la génération de l'enveloppe.",$api);
}

// Création de l'archive .tar.gz
if (!$env->generateArchiveFile()) {
	sortir("Erreur lors de la génération de l'archive.\n" . $env->getErrorMsg(),$api);
}

// Contrôle de l'archive (anti-virus et taille)
if (!$env->checkArchiveConformity()) {
	sortir("L'archive générée n'est pas conforme :\n" . $env->getErrorMsg(),$api);
}

// Purge des fichiers intermédiaires
$env->purgeFiles();

if (!$env->save()) {
  $msg = "Erreur lors de l'enregistrement de l'enveloppe :\n" . $env->getErrorMsg();
  if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
    $msg .= "\nErreur de journalisation.";
  }
	sortir($msg,$api);
}

$trans->set("envelope_id", $env->getId());

if (!$trans->save()) {
  $msg = "Erreur lors de l'enregistrement de la transaction :\n" . $trans->getErrorMsg();
  if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
    $msg .= "\nErreur de journalisation.";
  }

  $env->deleteArchiveFile();
  $env->delete();
  sortir($msg,$api);
} else {
  $msg = "Création de l'envelope n°" . $env->getId() . ". Résultat ok.";
  if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
    $msg .= "\nErreur de journalisation.";
  }

  // Message réservé à l'appel via API
  // Id de la transaction créée
  $apiMsg = $trans->getId() . "\n";
 
}
if ($api) {
	echo "OK : id généré : ".$apiMsg;	
} else {
	Helpers :: returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $trans->getId(), $apiMsg);
}


?>