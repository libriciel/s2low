<?php

use S2low\Services\Helios\HeliosAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Model\HeliosTransactionsSQL;

list($workerScript, $heliosTransactionSQL) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [WorkerScript::class, HeliosTransactionsSQL::class]
    );

$module = new Module();
if (! $module->initByName("helios")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (! $module->isActive() || ! $me->checkDroit("helios", "TT")) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}


$id = Helpers :: getVarFromPost("id");
if (empty($id)) {
    $_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
    header("Location: " . Helpers::getLink("/modules/helios/index.php"));
    exit();
}


$currentStatusId = HeliosTransactionWorkflow::getCurrentStatusId($id);
if ($currentStatusId != 14) {
    $_SESSION["error"] = "\nLa transaction n'est pas dans le bon état";
    header("Location: " . Helpers::getLink("/modules/helios/helios_transac_show.php?id=") . $id);
    exit();
}

$htw = new HeliosTransactionWorkflow();

$htw->set("transaction_id", $id);
$htw->set("status_id", 1);
$htw->set("message", "Fichier bien reçu par la plate-forme S2low");

$htw->set("date", date('Y-m-d H:i:s'));

if (!$htw->save(true)) {
    $msg = "Erreur de l'initialisaton de l'accès à la table helios_transactions_workflow.";
    $_SESSION["error"] = $msg;
    if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
        $_SESSION["error"] .= "\nErreur de journalisation.";
    }
    header("Location: " . Helpers::getLink("/modules/helios/index.php"));
    exit();
}

$heliosTransactionSQL->setLastStatusId($id);


$msg = "Préparation de la télétransmission Transaction n°" . $id . ". Résultat ok.";
if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
    $msg .= "\nErreur de journalisation.";
}

$workerScript->putJobByQueueName(HeliosAnalyseFichierAEnvoyerWorker::QUEUE_NAME, $id);

Helpers :: returnAndExit(0, "Préparation de la télétransmission réusssie.", Helpers::getLink("/modules/helios/helios_transac_show.php?id=") . $id);
