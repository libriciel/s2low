<?php

use S2low\Services\Validators\XadesSignatureValidator;
use S2lowLegacy\Class\LegacyObjectsManager;

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();
$xadesSignatureValidator = LegacyObjectsManager::getObject(XadesSignatureValidator::class);

if (empty($argv[1])) {
    echo "Usage : {$argv[0]} fichier_xades.xml\n";
    exit;
}

$xml_file = $argv[1];

echo "Analyse du fichier : $xml_file\n";

$xadesSignatureValidationResult = $xadesSignatureValidator->verifyWithReturn($xml_file);

echo "Vérification : " . ($xadesSignatureValidationResult->verification_success ? "OK" : "FAIL") . "\n";

if (! $xadesSignatureValidationResult->verification_success) {
    echo $xadesSignatureValidationResult->errorMessage . "\n";
}
