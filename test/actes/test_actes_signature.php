<?php

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPKCS7Signature;

require_once(__DIR__ . '/../../init/init.php');

$options = getopt('', [
    'use-wrong-file',
    'use-wrong-date',
]);

$useWrongFile = isset($options['use-wrong-file']);
$useWrongDate = isset($options['use-wrong-date']);

$signature = __DIR__ . '/../PHPUnit/class/fixtures/signaturesPKCS7/test_pdf.pdf.p7s';
$file_path = __DIR__ . '/../PHPUnit/class/fixtures/signaturesPKCS7/test_pdf.pdf';
$dateTime = null;

if ($useWrongFile) {
    $file_path = __DIR__ . '/../PHPUnit/class/fixtures/vide.pdf';
}

if ($useWrongDate) {
    $dateTime = new DateTime('01-01-1980');
}


$RGSCaPath = LegacyObjectsManager::getLegacyObjectInstancier()->getParameter('app.path_to_rgs_valid_cargs');
/** @var VerifyPKCS7Signature $verifyPKCS7Signature */
$verifyPKCS7Signature = LegacyObjectsManager::getLegacyObjectInstancier()->get(VerifyPKCS7Signature::class);

try {
    $verifyPKCS7Signature->verifySignature(
        file_get_contents($signature),
        $RGSCaPath,
        VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
        $file_path,
        $dateTime
    );
} catch (Throwable $e) {
    echo "La signature est invalide\n";
    var_dump($e->getMessage());
    return;
}

echo "La signature est valide\n";
