<?php

use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\XadesSignature;
use S2lowLegacy\Lib\XadesSignatureParser;

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

if (empty($argv[1])) {
    echo "Usage : {$argv[0]} fichier_xades.xml\n";
    exit;
}

$xml_file = $argv[1];

echo "Analyse du fichier : $xml_file\n";

$xadesSignature = new XadesSignature(
    XMLSEC1_PATH,
    EXTENDED_VALIDCA_PATH,
    new XadesSignatureParser(),
    new PemCertificateFactory(),
    new VerifyPemCertificate(new OpenSSLWrapper(new CommandLauncher()))
);

$xadesSignatureValidationResult = $xadesSignature->verifyWithReturn($xml_file);

echo "Vérification : " . ($xadesSignatureValidationResult->verification_success ? "OK" : "FAIL") . "\n";

if (! $xadesSignatureValidationResult->verification_success) {
    echo $xadesSignatureValidationResult->errorMessage . "\n";
}
