<?php

require_once("../../../init/init.php");
list($actesSignature, $actesTransactionsSQL, $actesEnvelopeSQL, $workerScript ) = LegacyObjectsManager::getLegacyObjectInstancier()
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
    header("Location: " . WEBSITE);
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
    header("Location:  " . Helpers::getLink("/modules/actes/actes_transac_show.php?id=$id"));
}

$all_transaction_id = array();

try {
    for ($i = 1; $i <= $nb_signature; $i++) {
        $signature = base64_decode(Helpers::getVarFromPost("signature_$i"));
        $signature_id = Helpers::getVarFromPost("signature_id_$i");
        $transaction_id = $actesSignature->setSignature($signature_id, $signature);
        $all_transaction_id[] = $transaction_id;
        /** Vérifier la signature ici */
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            RGS_VALIDCA_PATH,
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory()
        );

        $verifyPKCS7Signature->verifyCertificate($signature);

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
}

if (count($all_transaction_id) == 1) {
    $_SESSION["error"] = "La signature a été enregistrée";
    header("Location:  " . Helpers::getLink("/modules/actes/actes_transac_show.php?id={$all_transaction_id[0]}"));
} else {
    $_SESSION["error"] = "Les signatures ont été enregistrées";
    header("Location:  " . Helpers::getLink("/modules/actes/index.php"));
}
