<?php

use S2low\Services\Helios\HeliosAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\Droit;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/** @var Initialisation $initialisation */
/** @var \S2lowLegacy\Class\Droit $droit */
/** @var HeliosTransactionsSQL $transactionSQL */
/** @var WorkerScript $workerScript */
/** @var UserContext $userContext */

[$initialisation,$droit,$transactionSQL,$workerScript, $userContext] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([Initialisation::class,Droit::class,HeliosTransactionsSQL::class,WorkerScript::class, UserContext::class]);

$initialisation->initModule($userContext, Initialisation::MODULENAMEHELIOS);

if (! $droit->isSuperAdmin($userContext->userInfo)) {
    $_SESSION['error'] = "Réservé au super admin";
    header('Location: index.php');
    exit_wrapper();
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->getInt('id');
$transactionInfo = $transactionSQL->getInfo($id);

$message = "La transaction $id est de nouveau à l'état posté.";

$transactionSQL->updateStatus($id, HeliosTransactionsSQL::POSTE, $message);
$transactionSQL->setInfoFromPESAller($id, [
    'nom_fic' => null,
    'cod_col' => null,
    'cod_bud' => null,
    'id_post' => null
]);

$workerScript->putJobByQueueName(HeliosAnalyseFichierAEnvoyerWorker::QUEUE_NAME, $id);


$_SESSION['error'] = $message;
header_wrapper("Location: helios_transac_show.php?id=$id");
exit_wrapper();
