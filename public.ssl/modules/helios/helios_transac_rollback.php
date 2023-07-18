<?php

use S2low\Services\Helios\HeliosAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $init */
/** @var HeliosTransactionsSQL $transactionSQL $ */
/** @var WorkerScript $workerScript */

[ $init,$transactionSQL,$workerScript ] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,HeliosTransactionsSQL::class,WorkerScript::class]);

$init->initHelios();

if (! $init->userIsSuperAdmin()) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->getInt('id');



$transactionInfo = $transactionSQL->getInfo($id);

$message = "La transaction $id est de nouveau à l'état posté.";

$transactionSQL->updateStatus($id, HeliosTransactionsSQL::POSTE, $message);
$transactionSQL->setInfoFromPESAller($id, array(
    'nom_fic' => null,
    'cod_col' => null,
    'cod_bud' => null,
    'id_post' => null
));

$workerScript->putJobByQueueName(HeliosAnalyseFichierAEnvoyerWorker::QUEUE_NAME, $id);


$_SESSION['error'] = $message;
header("Location: helios_transac_show.php?id=$id");
