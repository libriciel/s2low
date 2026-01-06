<?php

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPKCS7Signature;

require_once __DIR__ . '/../../init/init.php';
$objectInstancier = LegacyObjectsManager::getLegacyObjectInstancier();

$signature = __DIR__ . '/../test/PHPUnit/class/fixtures/signaturesPKCS7/test_pdf.pdf.p7s';
$file = __DIR__ . '/../test/PHPUnit/class/fixtures/signaturesPKCS7/test_pdf.pdf';

/** @var VerifyPKCS7Signature $verifyPKCS7Signature */
$verifyPKCS7Signature = LegacyObjectsManager::getLegacyObjectInstancier()->get(VerifyPKCS7Signature::class);

$verifyPKCS7Signature->verifySignature(
    $signature,
    VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
    $file,
    new DateTime('01-01-2025')
);
