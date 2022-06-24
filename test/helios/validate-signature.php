<?php

require_once(__DIR__ . "/../../init/init.php");

if (empty($argv[1])) {
    echo "Usage : {$argv[0]} fichier_xades.xml\n";
    exit;
}

$xml_file = $argv[1];

echo "Analyse du fichier : $xml_file\n";

$xadesSignature = new XadesSignature(
    XMLSEC1_PATH,
    new PKCS12(),
    new X509Certificate(),
    EXTENDED_VALIDCA_PATH,
    new XadesSignatureParser(),
    new PemCertificateFactory(),
    new VerifyPemCertificate(EXTENDED_VALIDCA_PATH)
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
