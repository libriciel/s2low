<?php

// Configuration
use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesEnvelopeSerialSQL;
use S2lowLegacy\Class\actes\TypeTransaction;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\WorkerScript;

$workerScript = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (! $me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", Helpers::getLink("connexion-status"));
}

// Un super admin ne peut pas accéder à cette page
if (! $module->isActive() || $me->isGroupAdminOrSuper() || ! $me->checkDroit($module->get("name"), 'TT')) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$related_id = Helpers::getVarFromPost("id");

$myAuthority = new Authority($me->get("authority_id"));

$rel_trans = new ActesTransaction();

if (isset($related_id) && ! empty($related_id)) {
    $rel_trans->setId($related_id);
    if ($rel_trans->init()) {
        $owner = new User($rel_trans->get("user_id"));
        $owner->init();
    } else {
        Helpers::returnAndExit(1, "Erreur d'initialisation de la transaction.", Helpers::getLink("/modules/actes/index.php"));
    }
} else {
    Helpers::returnAndExit(1, "Pas d'identifiant de transaction à annuler spécifié.", Helpers::getLink("/modules/actes/index.php"));
}

if (!$rel_trans->isType(TypeTransaction::TransmissionActe)) {
    Helpers::returnAndExit(1, "Ce type de transaction ne peut pas être annulé.", Helpers::getLink("/modules/actes/actes_transac_show.php?id=") . $rel_trans->getId());
}

if ($rel_trans->hasPendingCancelTrans()) {
    Helpers::returnAndExit(1, "Une demande d'annulation est déjà en cours pour cette transaction.", Helpers::getLink("/modules/actes/actes_transac_show.php?id=") . $rel_trans->getId());
}

$rel_envelope = new ActesEnvelope($rel_trans->get("envelope_id"));
$rel_envelope->init();

// Vérification des permissions
if (
    ! ($me->isAdmin() && $me->get("authority_id") == $owner->get("authority_id"))
    && ! ($me->getId() == $rel_envelope->get("user_id") && $me->checkDroit($module->get("name"), 'TT'))
) {
    Helpers::returnAndExit(1, "Accès refusé.", Helpers::getLink("/modules/actes/index.php"));
}

if ($rel_trans->getCurrentStatus() != 4) {
    Helpers::returnAndExit(1, "Impossible d'annuler une transaction qui n'est pas en état « Acquittement reçu ».", Helpers::getLink("/modules/actes/actes_transac_show.php?id=") . $rel_trans->getId());
}

$env = new ActesEnvelope();
$trans = new ActesTransaction();
$transNatures = ActesTransaction::getTransactionNaturesIdDescr();

// Définition des adresses de retour
$retMail = array();
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
$env->set("return_mail", implode('|', $retMail));
$env->set("name", $me->getprettyName());
$env->set("telephone", $telephone);
$env->set("email", $me->get("email"));

// Initialisation de la transaction
$trans->setType(TypeTransaction::Annulation);
$trans->set("related_transaction", $rel_trans);
$trans->set("related_transaction_id", $related_id);
$trans->set("number", $rel_trans->get("number"));
$trans->set("unique_id", $rel_trans->get("unique_id"));
$trans->set("user_id", $rel_trans->get('user_id'));
$trans->set("authority_id", $rel_trans->get('authority_id'));
$trans->set("classification", $rel_trans->get('classification'));
$trans->set("classification_date", $rel_trans->get('classification_date'));
$trans->set("unique_id", $rel_trans->get('unique_id'));

// Destination de création des fichiers
$dest = $env->get("siren") . "/" . $trans->get("number") . "/";
$trans->set("destDir", $dest);
$env->set("destDir", $dest);


// Génération du fichier XML de la transaction
$xml_name = $trans->getStdFileName($env, false);
if (! $trans->generateMessageXMLFile($xml_name)) {
    Helpers::returnAndExit(1, "Erreur lors de la génération du message métier : " . $trans->getErrorMsg(), Helpers::getLink("/modules/actes/actes_transac_show.php?id=$related_id"));
}

$env->addTransaction($trans);



$authority_id = $me->get("authority_id");

$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
$serialNumber = $actesEnvelopeSerial->getNext($authority_id);

// Génération du fichier XML de l'enveloppe
if (! $env->generateEnvelopeXMLFile($serialNumber)) {
    Helpers::returnAndExit(1, "Erreur lors de la génération de l'enveloppe.", Helpers::getLink("/modules/actes/actes_transac_show.php?id=$related_id"));
}

// Création de l'archive .tar.gz
if (! $env->generateArchiveFile()) {
    Helpers::returnAndExit(1, "Erreur lors de la génération de l'archive.\n" . $env->getErrorMsg(), Helpers::getLink("/modules/actes/actes_transac_show.php?id=$related_id"));
}

// Purge des fichiers intermédiaires
$env->purgeFiles();

if (! $env->save()) {
    $msg = "Erreur lors de l'enregistrement de l'enveloppe&nbsp;:\n" . $env->getErrorMsg();

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    Helpers::returnAndExit(1, $msg, Helpers::getLink("/modules/actes/actes_transac_show.php?id=$related_id"));
}

$trans->set("envelope_id", $env->getId());

if (! $trans->save()) {
    $msg = "Erreur lors de l'enregistrement de la transaction.<br />" . $trans->getErrorMsg();

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    $env->deleteArchiveFile();
    $env->delete();

    Helpers::returnAndExit(1, $msg, Helpers::getLink("/modules/actes/actes_transac_show.php?id=") . $rel_trans->getId());
} else {
    $msg = "Création transaction d'annulation réussie. Enveloppe n°" . $env->getId() . " créée.";

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
        $msg .= "\nErreur de journalisation.";
    }

    Helpers::purgeTempSession();


    $workerScript->putJobByClassName(ActesAntivirusWorker::class, $trans->getId());


    // Message réservé à l'appel via API
  // Id de transaction créée
    $apiMsg = $trans->getId() . "\n";

    Helpers::returnAndExit(0, $msg, Helpers::getLink("/modules/actes/actes_transac_show.php?id=") . $rel_trans->getId(), $apiMsg);
}
