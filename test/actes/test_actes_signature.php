<?php

use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPemCertificateFactory;
use S2lowLegacy\Class\VerifyPKCS7Signature;
use S2lowLegacy\Class\VerifyPKCS7SignatureFactory;
use S2lowLegacy\Lib\PemCertificateFactory;

$file_path = "/Users/eric/Desktop/test/034-123456725-20151201-TESTS132-AU-1-1_1.pdf";

$file_manifest_path = "/Users/eric/Desktop/test/034-123456725-20151201-TESTS132-AU-1-1_0.xml";

$dom = simplexml_load_file($file_manifest_path);

$namespaces = $dom->getDocNamespaces();
// Récupération des éléments dans le namespace "actes"
$actesItems = $dom->children($namespaces["actes"]);


$signature =  $actesItems->Document->Signature . "\n";


$verifyPKCS7Signature = VerifyPKCS7SignatureFactory::create();

$verifyPKCS7Signature->verifySignature($signature, VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS);
