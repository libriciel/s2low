<?php

use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesSignature;
use S2lowLegacy\Class\actes\ActesStoreEnveloppeWorker;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPemCertificateFactory;
use S2lowLegacy\Class\VerifyPKCS7Signature;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\PemCertificateFactory;

/** @var Initialisation $initialisation */
/** @var ActesSignature $actesSignature */
/** @var ActesTransactionsSQL $actesTransactionsSQL */
/** @var ActesEnvelopeSQL $actesEnvelopeSQL */
/** @var WorkerScript $workerScript */
list(
    $initialisation,
    $actesSignature,
    $actesTransactionsSQL,
    $actesEnvelopeSQL,
    $workerScript
    ) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [
            Initialisation::class,
            ActesSignature::class,
            ActesTransactionsSQL::class,
            ActesEnvelopeSQL::class,
            WorkerScript::class
        ]
    );

$initData = $initialisation->doInit();
$initialisation->initModule($initData, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

// Instanciation du module courant
$module = new Module();
if (!$module->initByName('actes')) {
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

if (!$module->isActive() || ! $me->checkDroit($module->get("name"), 'CS')) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$nb_signature = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost('nb_signature');
if ($nb_signature == 0) {
    $_SESSION['error'] = "Les signatures n'ont pas pu être récupérées";
    header('Location:  ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
}

$all_transaction_id = [];

try {
    for ($i = 1; $i <= $nb_signature; $i++) {
        $signature = base64_decode(\S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("signature_$i"));
        $signature_id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("signature_id_$i");
        $transaction_id = $actesSignature->setSignature($signature_id, $signature);
        $all_transaction_id[] = $transaction_id;
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            RGS_VALIDCA_PATH,
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory(),
            new OpenSSLWrapper(
                RGS_VALIDCA_PATH,
                new CommandLauncher()
            )
        );
        $verifyPKCS7Signature->verifySignature($signature, VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS);

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $actesEnvelopeSQL->setTransactionInCloudRemove($transaction_info['envelope_id']);

        $workerScript->putJobByClassName(
            ActesStoreEnveloppeWorker::class,
            $transaction_info['envelope_id']
        );

        $workerScript->putJobByClassName(
            ActesAnalyseFichierAEnvoyerWorker::class,
            $transaction_info['envelope_id']
        );
    }
} catch (Exception $exception) {
    $_SESSION['error'] = 'Erreur lors de la signature : ' . $exception->getMessage();
    header('Location:  ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
} catch (Throwable $exception) {
    $_SESSION['error'] = '[ Throwable ] Erreur lors de la signature : ' . $exception->getMessage();
    header('Location:  ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
}

if (count($all_transaction_id) == 1) {
    $_SESSION['error'] = 'La signature a été enregistrée';
    header('Location:  ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_transac_show.php?id=$all_transaction_id[0]"));
} else {
    $_SESSION['error'] = 'Les signatures ont été enregistrées';
    header('Location:  ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/index.php'));
}
