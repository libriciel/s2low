<?php


require_once (__DIR__."/../../../config/config.php");
require_once (__DIR__ . '/../../../class/include.class.php');
require_once (__DIR__ . '/class/ActesEnvelope.class.php');
require_once (__DIR__ . '/class/ActesClassification.class.php');
require_once (__DIR__ . '/class/ActesBatch.class.php');
require_once (__DIR__ . '/../../../class/FileUploader.class.php');


$errorMsg = "";
$extraRedirect = "";
if (empty($_POST)){
	Helpers :: returnAndExit(1, "La taille totale des fichiers est trop importante (max : ". ACTES_ARCHIVE_MAX_SIZE .")", WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
  Helpers :: returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
  Helpers :: returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || ! $me->checkDroit($module->get("name"),'CS')) {
  Helpers :: returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

if ($module->getParam("paper") == "on") {
  Helpers :: returnAndExit(1, "Mode « papier » actif. Accès interdit.", WEBSITE_SSL . "/modules/actes/");
}

$must_signed = Helpers::getVarFromPost("must_signed",true);

$rgsConnexion = new RgsConnexion();
if ( ! $must_signed && ! $rgsConnexion->isRgsConnexion()){
	Helpers :: returnAndExit(1, "La télétransmission nécessite un certificat RGS<br/>Erreur : {$rgsConnexion->getLastMessage()}", WEBSITE_SSL . "/modules/actes/");
}


$myAuthority = new Authority($me->get("authority_id"));

// Recuperation des variables du POST
$nature_code = Helpers :: getVarFromPost("nature_code", true);
$en_attente = Helpers :: getVarFromPost("en_attente", true);

for ($i = 1; $i <= 5; $i++) {
  ${ "classif" . $i } = Helpers :: getVarFromPost("classif" . $i, true);
}

$number = Helpers :: getVarFromPost("number", true);
$decision_date = Helpers :: getVarFromPost("decision_date", true);

if (strtotime($decision_date) > time()){
	Helpers :: returnAndExit(1, "La date de décision est une date dans le futur", WEBSITE_SSL );
}

$document_papier =  Helpers :: getVarFromPost("document_papier", true)?1:0;

$subject = Helpers :: getVarFromPost("subject", true);
$subject = cp1252_to_iso88591($subject);

$batchFileId = Helpers :: getVarFromPost("batchfile");

if (isset($_FILES['acte_pdf_file'])) {
  $actePDFFile = $_FILES["acte_pdf_file"];
} else {
  $actePDFFile = false;
}

if (isset($_FILES["acte_pdf_file_sign"])){
	$actePDFFileSign = $_FILES["acte_pdf_file_sign"];
}

if (isset($_FILES["acte_attachments"])) {
$acteAttachments = $_FILES["acte_attachments"];
}
if (isset($_FILES["acte_attachments_sign"])){
	$acteAttachmentsSign = $_FILES["acte_attachments_sign"];
}

$type_pj = Helpers::getVarFromPost('type_pj',true);



$auto_broadcast_email = Helpers :: getVarFromPost("show_broadcast_email", true);
$broadcast_send_sources = Helpers :: getVarFromPost("send_sources", true);
$broadcast_string = Helpers :: getVarFromPost("broadcast_email", true);


if ($broadcast_string){
	$broadcast_emails = implode($broadcast_string,",");
} else {
	$broadcast_emails = false;
}

$processNextBatch = Helpers :: getVarFromPost("process_next_batch_file", true);

$processNextBatch = (isset ($processNextBatch) && $processNextBatch == "on") ? true : false;
$extraRedirect = "";

// Détermination si traitement par lot ou pas
$batchMode = false;
if (isset ($batchFileId) && is_numeric($batchFileId)) {
  $zeBatchFile = new ActesBatchFile($batchFileId);
  if ($zeBatchFile->init()) {
    $zeBatch = new ActesBatch($zeBatchFile->get("batch_id"));
    if ($zeBatch->init()) {
      $owner = new User($zeBatch->get("user_id"));
      $owner->init();

      // Vérification des permissions sur le lot
      if (($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) ||
      		($me->getId() == $owner->getId())) {
        $batchMode = true;
        $extraRedirect = "?batchfile=" . $zeBatchFile->getId();
      }
    }
  }

  // Les vérifs ont échouées
  if (!$batchMode) {
    Helpers :: returnAndExit(1, "Échec de la transaction en mode lot.", WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
  } 
}

$env = new ActesEnvelope();
$trans = new ActesTransaction();
$transNatures = ActesTransaction :: getTransactionNaturesIdDescr();

// Définition des adresses de retour
$retMail = array ();
$retMail[] = ACTES_TDT_MAIL_ADDRESS;

if ($me->get("email")) {
  $retMail[] = $me->get("email");
}
if ($myAuthority->get("email")) {
  $retMail[] = $myAuthority->get("email");
}

// Téléphone du contact
if ($me->get("telephone")) {
  $telephone = $me->get("telephone");
} else {
  $telephone = $myAuthority->get("telephone");
}


// Initialisation de l'enveloppe
$env->set("user_id", $me->getId());
$env->set("siren", $myAuthority->get("siren"));
$env->set("department", $myAuthority->get("department"));
$env->set("district", $myAuthority->get("district"));
$env->set("authority_type_code", $myAuthority->get("authority_type_id"));
$env->set("return_mail", implode($retMail, '|'));
$env->set("name", $me->getPrettyName());
$env->set("telephone", $telephone);
$env->set("email", $me->get("email"));
$env->set("file_path", "");

// Initialisation de la transaction
$trans->set("type", "1");
$trans->set("nature_code", $nature_code);
$trans->set("nature_descr", $transNatures[$nature_code]);
$trans->set("subject", $subject);
$trans->set("document_papier",$document_papier);
$trans->set("number", $number);
$trans->set("user_id",$me->getId());
$trans->set("authority_id",$me->get("authority_id"));
$trans->setEnAttente($en_attente);

$classification = array();
for ($i = 1; $i <= 5; $i++) {
  $trans->set("classif" . $i, ${ "classif" . $i });
	$classification[] = ${ "classif" . $i };
}

$actesClassificationCodesSQL = new ActesClassificationCodesSQL($sqlQuery);
$classification_description = $actesClassificationCodesSQL->getDescription($myAuthority->getId(),$classification);
$trans->set("classification_string",$classification_description);

$trans->set("classification_date", ActesClassification :: getLastRevisionDate($myAuthority->getId()));


$trans->set("decision_date", $decision_date);

if ($auto_broadcast_email == 'on') {
  $trans->set("broadcast_send_sources", ($broadcast_send_sources == 'on') ? 1 : 0);
  $trans->set("broadcast_emails", $broadcast_emails);
}

$trans->set("broadcasted", 'FALSE');

// Vérification qu'une transaction ayant le même numéro interne n'existe pas déjà
if (!$trans->isUnique($myAuthority->getId())) {
  Helpers :: returnAndExit(1, "Un acte portant le même numéro interne existe déjà dans la base de données.\nIl faut peut-être ajouter un suffixe au numéro.", WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

// Destination de création des fichiers
$dest = $env->get("siren") . "/" . $trans->get("number") . "/";



$trans->set("destDir", $dest);
$env->set("destDir", $dest);

// On essaye d'importer tous les fichiers même si une erreur se produit
// lors de l'import de l'un d'eux. On trace les erreurs avec un booléen.
$fileImportError = false;

$uploader = new FileUploader();

if (!$batchMode && empty($actePDFFile)) {
  Helpers:: returnAndExit(1, "Aucun fichier acte n'a été posté ", WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

// Validation du type des fichiers uploadés
// Fichier de l'acte
if (isset ($actePDFFile) || $batchMode) {
  if ($batchMode) {
    $acteFilePath = $zeBatchFile->getAbsoluteFilePath();
    $acteFileName = $zeBatchFile->getDisplayName();
  } else {
  	
	if (! $uploader->verifOK("acte_pdf_file")){
		Helpers :: returnAndExit(1, "Erreur lors de la récéption du fichier : " .$uploader->getLastError() , WEBSITE_SSL . "/modules/actes/actes_transac_add.php");
	}
  	
	$acteFilePath = $actePDFFile["tmp_name"];
	$acteFileName = $actePDFFile["name"];
	
  }

  $dest_name = $trans->getStdFileName($env);
  if (!$trans->addActeFile($acteFileName, $dest_name, $acteFilePath)) {
    $errorMsg .= "Erreur de validation du fichier de l'acte :\n" . $trans->getErrorMsg() . "\n";
    $fileImportError = true;
  } else {
    // Ajout de la signature si présente
    $signFile = null;
    if ($batchMode) {
      $sign = $zeBatchFile->get("signature");
      if (!empty ($sign)) {
        $signFile = $zeBatchFile->get("signature");
        $readFile = false;
      }
    } else {
      if (isset ($actePDFFileSign["tmp_name"]) && is_uploaded_file($actePDFFileSign["tmp_name"])) {
        $signFile = $actePDFFileSign["tmp_name"];
        $readFile = true;
      }
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
if (isset ($acteAttachments)) {
	
	
	if (! $uploader->verifOKAll("acte_attachments")){
		Helpers :: returnAndExit(1, "Erreur lors de la récéption du fichier : " .$uploader->getLastError() , WEBSITE_SSL . "/modules/actes/actes_transac_add.php");
	}

	for ($i = 0; $i < count($acteAttachments["tmp_name"]); $i++) {
		if (! strlen($acteAttachments["tmp_name"][$i])){
			continue;
		}

        $dest_name = $trans->getStdFileName($env,true,$type_pj[$i]);
        if (!$trans->addAttachmentFile($acteAttachments["name"][$i], $dest_name, $acteAttachments["tmp_name"][$i],true,$type_pj[$i])) {
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
      
    }
}


if ($fileImportError) {
  Helpers :: returnAndExit(1, $errorMsg, WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

// Génération du fichier XML de l'acte
$xml_name = $trans->getStdFileName($env, false);
if (!$trans->generateMessageXMLFile($xml_name)) {
  Helpers :: returnAndExit(1, "Erreur lors de la génération de l'acte : " . $trans->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

$env->addTransaction($trans);

$authority_id = $me->get("authority_id");

$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
$serialNumber = $actesEnvelopeSerial->getNext($authority_id);

// Génération du fichier XML de l'enveloppe
if (!$env->generateEnvelopeXMLFile($serialNumber)) {
  Helpers :: returnAndExit(1, "Erreur lors de la génération de l'enveloppe. ".$env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

// Création de l'archive .tar.gz
if (!$env->generateArchiveFile()) {
  Helpers :: returnAndExit(1, "Erreur lors de la génération de l'archive.\n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

if (!$env->checkArchiveSize())
	Helpers :: returnAndExit(1, "la taille d'archive générée n'est pas conforme. filename=".$env->get("file_path")."\n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);

/*if (!$env->checkArchiveSanity()) {
	Helpers :: returnAndExit(1, "L'archive générée porte des virus \n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}*/

	
// Purge des fichiers intermédiaires
$env->purgeFiles();


if (!$env->save()) {
  $msg = "Erreur lors de l'enregistrement de l'enveloppe :\n" . $env->getErrorMsg();
  if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
    $msg .= "\nErreur de journalisation.";
  }

  Helpers :: returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

$trans->set("envelope_id", $env->getId());
if ($must_signed){
	$trans->setEnAttenteDeSignature(true);
} elseif($me->getPerm("actes") == 'CS') {
	$trans->setEnAttente(true);
}

if (!$trans->save()) {
  $msg = "Erreur lors de l'enregistrement de la transaction :\n" . $trans->getErrorMsg();
  if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
    $msg .= "\nErreur de journalisation.";
  }

  $env->deleteArchiveFile();
  $env->delete();
  Helpers :: returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
} 


  $msg = "Création de l'enveloppe n°" . $env->getId() . ". Résultat ok.";
  if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
    $msg .= "\nErreur de journalisation.";
  }

  // Message réservé à l'appel via API
  // Id de la transaction créée
  $apiMsg = $trans->getId() . "\n";

  $nextBatchFileId = null;
  if ($batchMode) {
    if ($processNextBatch) {
      $nextBatchFileId = $zeBatch->getNextUnprocessedId($zeBatchFile->getId());
      if (!$nextBatchFileId) {
        $msg .= "\nLe lot courant ne comporte plus de fichier non traité.";
      }
    }


//On incrémente le suffixe
$zeBatch->incNextSuffix();

// On marque le fichier du lot comme Traité
    $zeBatchFile->setProcessed();
    $zeBatchFile->set("transaction_id", $trans->getId());
    if (!$zeBatchFile->save()) {
      $msg .= "\nErreur lors de la cloture du fichier de lot.";
    } else {
      // Suppression physique du fichier
      if (!$zeBatchFile->deleteFile()) {
        $msg .= "\nErreur lors de la suppression du fichier de lot.";
      }
    }
}

if ($nextBatchFileId) {
    Helpers :: returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_add.php?batchfile=" . $nextBatchFileId, $apiMsg);
} else {
    Helpers :: purgeTempSession();
    Helpers :: returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $trans->getId(), $apiMsg);
}

