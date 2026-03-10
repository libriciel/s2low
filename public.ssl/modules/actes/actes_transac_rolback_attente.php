<?php

use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\Recuperateur;

/** @var Initialisation $initialisation */
/** @var Droit $droit */
/** @var ActesTransactionsSQL $actesTransactionSQL */
/** @var WorkerScript $workerScript */
/** @var UserContext $userContext */

[$initialisation,$droit,$actesTransactionSQL,$workerScript, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class, Droit::class,ActesTransactionsSQL::class,WorkerScript::class, UserContext::class]);

$initialisation->initModule($userContext, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

if (! $droit->isSuperAdmin($userContext->userInfo)) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = (int) $recuperateur->get('id');

$status_id = (int) $recuperateur->get('status_id', ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION);

switch ($status_id) {
    case ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION:
        $message = "La transaction $id a été passée manuellement en attente de transmission";
        $workerQueueName = ActesEnvoiFichierWorker::QUEUE_NAME;
        $queueTTR = ActesEnvoiFichierWorker::PHEANSTALK_TTR;
        break;

    case ActesStatusSQL::STATUS_POSTE:
        $message = "La transaction $id a été passée manuellement en posté";
        $workerQueueName = ActesAnalyseFichierAEnvoyerWorker::QUEUE_NAME;
        $queueTTR = ActesAnalyseFichierAEnvoyerWorker::PHEANSTALK_TTR;

        break;

    default:
        $_SESSION['error'] = "Impossible de passer la transaction $id dans l'état $status_id.";
        header("Location: actes_transac_show.php?id=$id");
        exit;
}


$actesTransactionSQL->updateStatus($id, $status_id, $message);

$info = $actesTransactionSQL->getInfo($id);

$workerScript->putJobByQueueName($workerQueueName, $info['envelope_id'], $queueTTR);

$_SESSION['error'] = $message;
header_wrapper("Location: actes_transac_show.php?id=$id");
exit_wrapper();
