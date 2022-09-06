<?php

use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\Recuperateur;

require_once(dirname(__FILE__) . "/../../../init/init-www-actes.php");

if (! $droit->isSuperAdmin($userInfo)) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = (int) $recuperateur->get('id');

$status_id = (int) $recuperateur->get('status_id', ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION);

switch ($status_id) {
    case ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION:
        $message = "La transaction $id a été passée manuellement en attente de transmission";
        $workerClassName = ActesEnvoiFichierWorker::class;
        break;

    case ActesStatusSQL::STATUS_POSTE:
        $message = "La transaction $id a été passée manuellement en posté";
        $workerClassName = ActesAnalyseFichierAEnvoyerWorker::class;
        break;

    default:
        $_SESSION['error'] = "Impossible de passer la transaction $id dans l'état $status_id.";
        header("Location: actes_transac_show.php?id=$id");
        exit;
}


$actesTransactionSQL = new ActesTransactionsSQL($sqlQuery);

$actesTransactionSQL->updateStatus($id, $status_id, $message);

$info = $actesTransactionSQL->getInfo($id);

$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->putJobByClassName($workerClassName, $info['envelope_id']);

$_SESSION['error'] = $message;
header("Location: actes_transac_show.php?id=$id");
