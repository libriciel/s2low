<?php

use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var Droit $droit */
/** @var ActesTransactionsSQL $actesTransactionSQL */
/** @var WorkerScript $workerScript */

[$initialisation,$droit,$actesTransactionSQL,$workerScript] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, Droit::class,ActesTransactionsSQL::class,WorkerScript::class]);

$initData = $initialisation->doInit(Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if (! $droit->isSuperAdmin($initData->userInfo)) {
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


$actesTransactionSQL->updateStatus($id, $status_id, $message);

$info = $actesTransactionSQL->getInfo($id);

$workerScript->putJobByClassName($workerClassName, $info['envelope_id']);

$_SESSION['error'] = $message;
header("Location: actes_transac_show.php?id=$id");
