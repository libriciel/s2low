<?php

require_once(dirname(__FILE__) . "/../../../init/init-www-helios.php");

if (! $droit->isSuperAdmin($userInfo)) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->getInt('id');

$transactionSQL = new HeliosTransactionsSQL($sqlQuery);

$transactionInfo = $transactionSQL->getInfo($id);

$message = "La transaction $id est de nouveau à l'état posté.";

$transactionSQL->updateStatus($id, HeliosTransactionsSQL::POSTE, $message);
$transactionSQL->setInfoFromPESAller($id, array(
    'nom_fic' => null,
    'cod_col' => null,
    'cod_bud' => null,
    'id_post' => null
));


$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->putJobByClassName(HeliosAnalyseFichierAEnvoyerWorker::class, $id);


$_SESSION['error'] = $message;
header("Location: helios_transac_show.php?id=$id");
