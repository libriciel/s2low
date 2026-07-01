<?php

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
    $this->getObjectInstancier()->get(VerifyPemCertificate::class)
);

$verify = true;
try {
    $xadesSignature->verify($xml_file);
} catch (Exception $e) {
    $verify = false;
}

echo "Vérification : " . ($verify ? "OK" : "FAIL") . "\n";

if (! $verify) {
    echo $xadesSignature->getLastOutput() . "\n";
}
