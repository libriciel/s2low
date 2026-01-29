<?php

use S2low\Services\Validators\PKCS7SignatureValidator;
use S2lowLegacy\Class\VerifyPemCertificate;

$file_path = "/Users/eric/Desktop/test/034-123456725-20151201-TESTS132-AU-1-1_1.pdf";

$file_manifest_path = "/Users/eric/Desktop/test/034-123456725-20151201-TESTS132-AU-1-1_0.xml";

$dom = simplexml_load_file($file_manifest_path);

$namespaces = $dom->getDocNamespaces();
// Récupération des éléments dans le namespace "actes"
$actesItems = $dom->children($namespaces["actes"]);


$signature =  $actesItems->Document->Signature . "\n";

/** @var PKCS7SignatureValidator $PKCS7SignatureValidator */
$PKCS7SignatureValidator = \S2lowLegacy\Class\LegacyObjectsManager::getObject(PKCS7SignatureValidator::class);

$PKCS7SignatureValidator->validate($signature, VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS);
