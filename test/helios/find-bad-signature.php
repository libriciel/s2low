<?php

use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2low\Services\SimpleXmlUtils\SignedChecker;
use S2low\Services\Validators\XadesSignatureValidator;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Lib\XadesSignature;
use S2lowLegacy\Lib\XadesSignatureParser;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);
$signedChecker = LegacyObjectsManager::getLegacyObjectInstancier()->get(SignedChecker::class);
$xadesSignatureValidator = LegacyObjectsManager::getObject(XadesSignatureValidator::class);

if (empty($argv[1])) {
    echo "Usage : {$argv[0]} YYYY-mm-dd\n";
    exit;
}
$date = $argv[1];

$sql = "SELECT id,sha1 FROM helios_transactions WHERE submission_date>? AND submission_date<?";
$transactions_list = $sqlQuery->query($sql, $date . " 00:00", $date . " 23:59:59");

$nb_transaction = count($transactions_list);

echo "Analyse de $nb_transaction fichiers\n";

$error_list = array();

foreach ($transactions_list as $num_transaction => $transaction_helios) {
    echo "Transaction {$transaction_helios['id']} ($num_transaction/$nb_transaction)\n";
    $pes_aller = HELIOS_FILES_UPLOAD_ROOT . "/{$transaction_helios['sha1']}";
    echo "Analyse du fichier : $pes_aller\n";

    if (! $signedChecker->isSigned($pes_aller)) {
        echo "Le fichier n'est pas signé\n";
        continue;
    }

    $xadesSignatureValidationResult = $xadesSignatureValidator->validate($pes_aller);

    echo "Vérification : " . ($xadesSignatureValidationResult->verification_success ? "OK" : "FAIL") . "\n";

    if (! $xadesSignatureValidationResult->verification_success) {
        $error_list[] = $transaction_helios['id'];
        echo $xadesSignatureValidationResult->errorMessage . "\n";
    }
}


echo count($error_list) . " transactions en erreur\n";


print_r($error_list);
