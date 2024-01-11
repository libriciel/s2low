<?php

use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesSignature;
use S2lowLegacy\Class\actes\ActesStoreEnveloppeWorker;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPemCertificateFactory;
use S2lowLegacy\Class\VerifyPKCS7Signature;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\PemCertificateFactory;

list($actesSignature, $actesTransactionsSQL, $actesEnvelopeSQL, $workerScript ) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ActesSignature::class, ActesTransactionsSQL::class, ActesEnvelopeSQL::class, WorkerScript::class]
    );

require_once(__DIR__ . "/../../../init/init-www-actes.php");

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (!$module->isActive() || ! $me->checkDroit($module->get("name"), 'CS')) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$nb_signature = Helpers::getVarFromPost("nb_signature");
if ($nb_signature == 0) {
    $_SESSION["error"] = "Les signatures n'ont pas pu être récupérées";
    header("Location:  " . Helpers::getLink("/modules/actes/index.php"));
}

$all_transaction_id = array();

try {
    for ($i = 1; $i <= $nb_signature; $i++) {
        $signature = base64_decode(Helpers::getVarFromPost("signature_$i"));
        $signature_id = Helpers::getVarFromPost("signature_id_$i");
        $transaction_id = $actesSignature->setSignature($signature_id, $signature);
        $all_transaction_id[] = $transaction_id;
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            RGS_VALIDCA_PATH,
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory(),
            new \S2low\Services\ProcessCommand\OpenSSLWrapper(
                RGS_VALIDCA_PATH,
                new \S2low\Services\ProcessCommand\CommandLauncher()
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
} catch (Exception $e) {
    $_SESSION["error"] = "Erreur lors de la signature : " . $e->getMessage();
    header("Location:  " . Helpers::getLink("/modules/actes/index.php"));
} catch (Throwable $e) {
    $_SESSION["error"] = "[ Throwable ] Erreur lors de la signature : " . $e->getMessage();
    header("Location:  " . Helpers::getLink("/modules/actes/index.php"));
}

if (count($all_transaction_id) == 1) {
    $_SESSION["error"] = "La signature a été enregistrée";
    header("Location:  " . Helpers::getLink("/modules/actes/actes_transac_show.php?id={$all_transaction_id[0]}"));
} else {
    $_SESSION["error"] = "Les signatures ont été enregistrées";
    header("Location:  " . Helpers::getLink("/modules/actes/index.php"));
}
