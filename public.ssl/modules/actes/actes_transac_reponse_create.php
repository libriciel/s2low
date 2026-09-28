<?php

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesEnvelopeSerialSQL;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\WorkerScript;

$errorMsg = "";

if (!function_exists('sortir_atrc')) {
    function sortir_atrc($message, $api): never
    {
        global $related_id;
        if ($api) {
            echo "KO : " . $message;
            exit;
        } else {
            Helpers:: returnAndExit(
                1,
                $message,
                Helpers::getLink("/modules/actes/actes_transac_repondre.php?id=$related_id")
            );
        }
    }
}

// Configuration
$workerScript = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);


$api = Helpers::getVarFromGet("api");
if ($api) {
    header("Content-type: text/plain");
}

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
    sortir_atrc("Erreur d'initialisation du module", $api);
}

$me = new User();

if (!$me->authenticate()) {
    sortir_atrc("Échec de l'authentification", $api);
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->checkDroit($module->get("name"), 'CS')) {
    sortir_atrc("Accès refusé", $api);
}

if ($module->getParam("paper") == "on") {
    sortir_atrc("Mode « papier » actif. Accès interdit.", $api);
}

$myAuthority = new Authority($me->get("authority_id"));

// Recuperation des variables du POST

$related_id = Helpers::getVarFromPost("id");


$related_trans = new ActesTransaction($related_id);
if (!$related_trans->init()) {
    sortir_atrc("Transaction inconnue", $api);
}
$related_owner = new User($related_trans->get('user_id'));
$related_owner->init();
$permission = new ModulePermission(new ServiceUser(DatabasePool::getInstance()), 'actes');
if (!$permission->canView($me, $related_owner)) {
    sortir_atrc("Accès refusé", $api);
}


$type_acte = Helpers::getVarFromPost('type_acte', true);
$type_pj = Helpers::getVarFromPost('type_pj', true);

if (empty($type_acte)) {
    Helpers:: returnAndExit(
        1,
        "Erreur lors de la réception du fichier : typologie absente",
        Helpers::getLink("/modules/actes/actes_transac_reponse.php?id=$related_id")
    );
}

$type_transaction = $related_trans->get("type");
$type_envoie = Helpers:: getVarFromPost("type_envoie", true);

$actePDFFile = $_FILES["acte_pdf_file"];
if (isset($_FILES["acte_pdf_file_sign"])) {
    $actePDFFileSign = $_FILES["acte_pdf_file_sign"];
} else {
    $actePDFFileSign = [];
}

if (isset($_FILES["acte_attachments"])) {
    $acteAttachments = $_FILES["acte_attachments"];
} else {
    $acteAttachments = ["tmp_name" => []];
}

if (isset($_FILES["acte_attachments_sign"])) {
    $acteAttachmentsSign = $_FILES["acte_attachments_sign"];
} else {
    $acteAttachmentsSign = [];
}

$env = new ActesEnvelope();
$trans = new ActesTransaction();

$retMail = array();
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
$env->set("return_mail", implode('|', $retMail));
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
$trans->set("type_reponse", $type_envoie);
$trans->set("user_id", $related_trans->get('user_id'));
$trans->set("authority_id", $related_trans->get('authority_id'));
$trans->set("classification", $related_trans->get('classification'));
$trans->set("classification_date", Helpers::getANSIDateFromBDDDate($related_trans->get('classification_date')));
$trans->set("unique_id", $related_trans->get('unique_id'));


// Destination de création des fichiers
$dest = $env->get("siren") . "/" . $trans->get("number") . "/";
$trans->set("destDir", $dest);
$env->set("destDir", $dest);

// On essaye d'importer tous les fichiers même si une erreur se produit
// lors de l'import de l'un d'eux. On trace les erreurs avec un booléen.
$fileImportError = false;

// Validation du type des fichiers uploadés
// Fichier de l'acte
if (isset($actePDFFile)) {
    if (is_uploaded_file_wrapper($actePDFFile["tmp_name"])) {
        $acteFilePath = $actePDFFile["tmp_name"];
        $acteFileName = $actePDFFile["name"];
    } else {
        sortir_atrc("Envoi de fichier illégal.", $api);
    }

    $dest_name = $trans->getStdFileName($env, true, $type_acte);
    if (!$trans->addActeFile($acteFileName, $dest_name, $acteFilePath, true, $type_acte)) {
        $errorMsg = "Erreur de validation du fichier de l'acte :\n" . $trans->getErrorMsg() . "\n";
        $fileImportError = true;
    } else {
        // Ajout de la signature si présente
        $signFile = null;

        $readFile = false;
        if (isset($actePDFFileSign["tmp_name"]) && is_uploaded_file_wrapper($actePDFFileSign["tmp_name"])) {
            $signFile = $actePDFFileSign["tmp_name"];
            $readFile = true;
        }

        if ($signFile) {
            if (!$trans->addActeSign($signFile, $readFile)) {
                $errorMsg .= "Erreur lors du traitement de la signature du fichier " . $acteFileName . " :\n" . $trans->getErrorMsg(
                ) . "\n";
                $fileImportError = true;
            }
        }
    }
}


for ($i = 0; $i < count($acteAttachments["tmp_name"] ?: []); $i++) {
    if (mb_strlen($acteAttachments["tmp_name"][$i])) {
        if (is_uploaded_file_wrapper($acteAttachments["tmp_name"][$i])) {
            if (empty($type_pj[$i])) {
                Helpers:: returnAndExit(
                    1,
                    "Erreur lors de la réception du fichier annexe {$acteAttachments["name"][$i]} : typologie absente",
                    Helpers::getLink("/modules/actes/actes_transac_add.php")
                );
            }

            if (empty($type_pj[$i])) {
                //Type par defaut des annexes
                $type_pj[$i] = '99_AU';
            }

            $dest_name = $trans->getStdFileName($env, true, $type_pj[$i]);
            if (
                !$trans->addAttachmentFile(
                    $acteAttachments["name"][$i],
                    $dest_name,
                    $acteAttachments["tmp_name"][$i],
                    true,
                    $type_pj[$i]
                )
            ) {
                $errorMsg .= "Erreur de validation d'un fichier de pièce jointe :\n" . $trans->getErrorMsg() . "\n";
                $fileImportError = true;
            } else {
                // Ajout de la signature si présente
                if (
                    isset($acteAttachmentsSign["tmp_name"][$i]) && is_uploaded_file_wrapper(
                        $acteAttachmentsSign["tmp_name"][$i]
                    )
                ) {
                    if (!$trans->addAttachmentSign($acteAttachmentsSign["tmp_name"][$i])) {
                        $errorMsg .= "Erreur lors du traitement de la signature du fichier " . $acteAttachments["name"][$i] . " :\n" . $trans->getErrorMsg(
                        ) . "\n";
                        $fileImportError = true;
                    }
                }
            }
        } else {
            sortir_atrc("Envoi de fichier illégal.", $api);
        }
    }
}


if ($fileImportError) {
    sortir_atrc($errorMsg, $api);
}

// Génération du fichier XML de l'acte
$xml_name = $trans->getStdFileName($env, false);
if (!$trans->generateMessageXMLFile($xml_name)) {
    sortir_atrc("Erreur lors de la génération de l'acte : " . $trans->getErrorMsg(), $api);
}

$env->addTransaction($trans);


$authority_id = $me->get("authority_id");

$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
$serialNumber = $actesEnvelopeSerial->getNext($authority_id);

// Génération du fichier XML de l'enveloppe
if (!$env->generateEnvelopeXMLFile($serialNumber)) {
    sortir_atrc("Erreur lors de la génération de l'enveloppe.", $api);
}

// Création de l'archive .tar.gz
if (!$env->generateArchiveFile()) {
    sortir_atrc("Erreur lors de la génération de l'archive.\n" . $env->getErrorMsg(), $api);
}

// Contrôle de l'archive (anti-virus et taille)
if (!$env->checkArchiveConformity()) {
    sortir_atrc("L'archive générée n'est pas conforme :\n" . $env->getErrorMsg(), $api);
}

// Purge des fichiers intermédiaires
$env->purgeFiles();

if (!$env->save()) {
    $msg = "Erreur lors de l'enregistrement de l'enveloppe :\n" . $env->getErrorMsg();
    if (!Log:: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }
    sortir_atrc($msg, $api);
}

$trans->set("envelope_id", $env->getId());

if (!$trans->save()) {
    $msg = "Erreur lors de l'enregistrement de la transaction :\n" . $trans->getErrorMsg();
    if (!Log:: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    $env->deleteArchiveFile();
    $env->delete();
    sortir_atrc($msg, $api);
} else {
    $msg = "Création de l'envelope n°" . $env->getId() . ". Résultat ok.";
    if (!Log:: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    // Message réservé à l'appel via API
    // Id de la transaction créée
    $apiMsg = $trans->getId() . "\n";
}

$workerScript->putJobByClassName(ActesAntivirusWorker::class, $trans->getId());


if ($api) {
    echo "OK : id généré : " . $apiMsg;
} else {
    Helpers:: returnAndExit(
        0,
        $msg,
        Helpers::getLink("/modules/actes/actes_transac_show.php?id=") . $trans->getId(),
        $apiMsg
    );
}
