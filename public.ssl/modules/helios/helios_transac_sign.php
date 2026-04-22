<?php

use S2low\Services\Helios\HeliosAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\helios\HeliosSignature;
use S2lowLegacy\Class\helios\HeliosStorePESAllerWorker;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Model\HeliosTransactionsSQL;

list($initialisation, $pesAllerRetriever,$workerScript, $heliosTransactionSQL ) =
    LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [Initialisation::class, PesAllerRetriever::class, WorkerScript::class, HeliosTransactionsSQL::class]
    );

$initData = $initialisation->doInit();
$initialisation->initModule($initData, Initialisation::MODULENAMEHELIOS);

// Instanciation du module courant
$module = new Module();
if (!$module->initByName('helios')) {
    $_SESSION['error'] = "Erreur d'initialisation du module";
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION['error'] = "Échec de l'authentification";
    header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('connexion-status'));
    exit();
}

if (!$module->isActive() || !$me->checkDroit($module->get('name'), 'CS')) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$nb_signature = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost('nb_signature');



for ($i = 1; $i <= $nb_signature; $i++) {
    $id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("id_$i");
    $signature_id_1 = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("signature_id_$i");
    $signature_1 = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("signature_$i");
    $is_bordereau_1 = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("is_bordereau_$i");

    if (empty($id)) {
        $_SESSION['error'] = "Pas d'identifiant de transaction spécifié";
        header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php'));
        exit();
    }


    $trans = new HeliosTransaction();
    $trans->setId($id);
    if (! $trans->init()) {
        $_SESSION['error'] = "Erreur d'initialisation de la transaction.";
        header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php'));
        exit();
    }

    if ($trans->get('last_status_id') != 13) {
        $_SESSION['error'] = 'Le fichier PES ne peut plus être signé à ce moment-là (status : ' . $trans->get('last_status_id') . ')';
        header('Location:  ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/helios_transac_show.php?id=$id"));
        exit();
    }


    $sha1 = $trans->get('sha1');

    $file_path = $pesAllerRetriever->getPath($trans->get('sha1'));

    $heliosSignature = new HeliosSignature();

    $new_pes_content = $heliosSignature->injectSignature($file_path, $signature_1, $is_bordereau_1);

    $new_sha1 = sha1($new_pes_content);
    $new_filesize = mb_strlen($new_pes_content);

    if ($new_filesize > HELIOS_MAX_UPLOAD_SIZE) {
        $_SESSION['error'] = 'Taille de fichier supérieure à la limite autorisée (' .
            (HELIOS_MAX_UPLOAD_SIZE / 1024 / 1024) . 'Mo maximum).';
        header('Location: ' . WEBSITE_SSL);
        exit();
    }

    $new_file_path = $pesAllerRetriever->getPathForNonExistingFile($new_sha1);

    file_put_contents($new_file_path, $new_pes_content);


    $heliosTransactionSQL->setTransactionInCloudRemove($id);

    $trans->set('sha1', $new_sha1);
    $trans->set('filesize', $new_filesize);

    if (! $trans->save()) {
        $msg =  "Erreur de l'enregistrement de la signature.";

        if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get('name'), $me)) {
            $_SESSION['error'] .= "\nErreur de journalisation.";
        }
        $_SESSION['error'] = $msg;
        header('Location:  ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/helios_transac_show.php?id=$id"));
        exit();
    }

    $heliosTransactionSQL->updateStatus($id, HeliosTransactionsSQL::POSTE, 'Fichier signé');

    $workerScript->putJobByClassName(HeliosStorePESAllerWorker::class, $id);
    $workerScript->putJobByQueueName(HeliosAnalyseFichierAEnvoyerWorker::QUEUE_NAME, $id);
}

if ($nb_signature > 1) {
    $_SESSION['error'] = 'Les signatures ont été enregistrées';
    header('Location:  ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/helios/index.php'));
    exit();
} else {
    $_SESSION['error'] = 'La signature a été enregistrée';
    header('Location:  ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/helios_transac_show.php?id=$id"));
    exit();
}
