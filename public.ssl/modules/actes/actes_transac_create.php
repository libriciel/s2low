<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à  la
 * dématérialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à  l'utilisation,  à la modification et/ou au
 * développement et à  la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à  
 * manipuler et qui le réserve donc à  des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à  charger  et  tester  l'adéquation  du
 * logiciel à  leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à  cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php

/**
 * \file actes_transac_create.php
 * \brief Page de traitement des ajouts de transactions
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 27.07.2006
 * 
 *
 * Cette page effectue le traitement d'ajout d'une
 * transaction dans la base de données
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesClassification.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesBatch.class.php');
// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
  Helpers :: returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
  Helpers :: returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canEdit($module->get("name"))) {
  Helpers :: returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

if ($module->getParam("paper") == "on") {
  Helpers :: returnAndExit(1, "Mode « papier » actif. Accès interdit.", WEBSITE_SSL . "/modules/actes/");
}

$myAuthority = new Authority($me->get("authority_id"));

// Recuperation des variables du POST
$nature_code = Helpers :: getVarFromPost("nature_code", true);

for ($i = 1; $i <= 5; $i++) {
  ${ "classif" . $i } = Helpers :: getVarFromPost("classif" . $i, true);
}

$number = Helpers :: getVarFromPost("number", true);
$decision_date = Helpers :: getVarFromPost("decision_date", true);
$subject = Helpers :: getVarFromPost("subject", true);
// pour corriger le bug 210 qund objet a un "\'" de dans, on le remplace comme un "'"
$subject = str_replace("\\","",$subject);
$batchFileId = Helpers :: getVarFromPost("batchfile");
$actePDFFile = $_FILES["acte_pdf_file"];
$actePDFFileSign = $_FILES["acte_pdf_file_sign"];
$acteAttachments = $_FILES["acte_attachments"];
$acteAttachmentsSign = $_FILES["acte_attachments_sign"];

$auto_broadcast_email = Helpers :: getVarFromPost("show_broadcast_email", true);
$broadcast_send_sources = Helpers :: getVarFromPost("send_sources", true);

$broadcast_string = Helpers :: getVarFromPost("broadcast_email", true);

if (strstr($subject,"&"))
{
  Helpers :: returnAndExit(1, "L'objet d'une transaction ne doit pas contenir une caractère '&'.", WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
	
}
if ($broadcast_string){
	$broadcast_emails = implode($broadcast_string,",");
} else {
	$broadcast_emails = array();
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
      if (($me->isAuthorityAdmin && $me->get("authority_id") == $owner->get("authority_id")) || ($me->getId() == $owner->getId())) {
        $batchMode = true;
      }
    }
  }

  // Les vérifs ont échouées
  if (!$batchMode) {
    Helpers :: returnAndExit(1, "Échec de la transaction en mode lot.", WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
  } else {
    $extraRedirect = "?batchfile=" . $zeBatchFile->getId();
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
$env->set("name", $me->getprettyName());
$env->set("telephone", $telephone);
$env->set("email", $me->get("email"));
$env->set("file_path", "");

// Initialisation de la transaction
$trans->set("type", "1");
$trans->set("nature_code", $nature_code);
$trans->set("nature_descr", $transNatures[$nature_code]);
$trans->set("subject", $subject);
$trans->set("number", $number);
for ($i = 1; $i <= 5; $i++) {
  $trans->set("classif" . $i, ${ "classif" . $i });
}

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

// Validation du type des fichiers uploadés
// Fichier de l'acte
if (isset ($actePDFFile) || $batchMode) {
  if ($batchMode) {
    $acteFilePath = $zeBatchFile->getAbsoluteFilePath();
    $acteFileName = $zeBatchFile->getDisplayName();
  } else {
    if (is_uploaded_file($actePDFFile["tmp_name"])) {
      $acteFilePath = $actePDFFile["tmp_name"];
      $acteFileName = $actePDFFile["name"];
    } else {
      Helpers :: returnAndExit(1, "Envoi de fichier illégal.", WEBSITE_SSL . "/modules/actes/actes_transac_add.php");
    }
  }

  $dest_name = $trans->getStdFileName($env);
  if (!$trans->addActeFile($acteFileName, $dest_name, $acteFilePath)) {
    $errorMsg = "Erreur de validation du fichier de l'acte :\n" . $trans->getErrorMsg() . "\n";
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
        Helpers :: returnAndExit(1, "Envoi de fichier illégal.", WEBSITE_SSL . "/modules/actes/actes_transac_add.php");
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
  Helpers :: returnAndExit(1, $errorMsg, WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

// Génération du fichier XML de l'acte
$xml_name = $trans->getStdFileName($env, false);
if (!$trans->generateMessageXMLFile($xml_name)) {
  Helpers :: returnAndExit(1, "Erreur lors de la génération de l'acte : " . $trans->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

$env->addTransaction($trans);

// Génération du fichier XML de l'enveloppe
if (!$env->generateEnvelopeXMLFile()) {
  Helpers :: returnAndExit(1, "Erreur lors de la génération de l'enveloppe.", WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

// Création de l'archive .tar.gz
if (!$env->generateArchiveFile()) {
  Helpers :: returnAndExit(1, "Erreur lors de la génération de l'archive.\n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}


// Contrôle de l'archive (anti-virus et taille)
/*if (!$env->checkArchiveConformity()) {
  Helpers :: returnAndExit(1, "L'archive générée n'est pas conforme. filename=".$env->get("file_path")."\n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}
*/
if (!$env->checkArchiveSize())
	Helpers :: returnAndExit(1, "la taille d'archive générée n'est pas conforme. filename=".$env->get("file_path")."\n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
if (!$env->checkArchiveSanity())
	Helpers :: returnAndExit(1, "L'archive générée porte des virus \n" . $env->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);

	
// Purge des fichiers intermédiaires
$env->purgeFiles();

//print_r($env);
//print_r($trans);

//exit();

if (!$env->save()) {
  $msg = "Erreur lors de l'enregistrement de l'enveloppe :\n" . $env->getErrorMsg();
  if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
    $msg .= "\nErreur de journalisation.";
  }

  Helpers :: returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
}

$trans->set("envelope_id", $env->getId());

if (!$trans->save()) {
  $msg = "Erreur lors de l'enregistrement de la transaction :\n" . $trans->getErrorMsg();
  if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
    $msg .= "\nErreur de journalisation.";
  }

  $env->deleteArchiveFile();
  $env->delete();
  Helpers :: returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_transac_add.php" . $extraRedirect);
} else {
  $msg = "Création de l'envelope n°" . $env->getId() . ". Résultat ok.";
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
}
?>