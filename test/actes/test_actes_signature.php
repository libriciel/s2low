<?php

use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPKCS7Signature;
use S2lowLegacy\Lib\PemCertificateFactory;

$file_path = "/Users/eric/Desktop/test/034-123456725-20151201-TESTS132-AU-1-1_1.pdf";

$file_manifest_path = "/Users/eric/Desktop/test/034-123456725-20151201-TESTS132-AU-1-1_0.xml";

$dom = simplexml_load_file($file_manifest_path);

$namespaces = $dom->getDocNamespaces();
// Récupération des éléments dans le namespace "actes"
$actesItems = $dom->children($namespaces["actes"]);


$signature =  $actesItems->Document->Signature . "\n";


$verifyPKCS7Signature = new VerifyPKCS7Signature(
    "/etc/tedetis/ssl/validca/",
    new OpenSSLWrapper(new CommandLauncher()),
    new VerifyPemCertificate(new OpenSSLWrapper(new CommandLauncher())),
    new PemCertificateFactory(),
);

$verifyPKCS7Signature->verifySignature($signature, VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS);
