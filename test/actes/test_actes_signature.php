<?php

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPKCS7Signature;

require_once __DIR__ . '/../../init/init.php';
$objectInstancier = LegacyObjectsManager::getLegacyObjectInstancier();


$file = $argv[1];
$signature = $argv[2];
$date = isset($argv[3]) ? new DateTime($argv[3]) : new DateTime();

$RGSCaPath = LegacyObjectsManager::getLegacyObjectInstancier()->getParameter('%app.path_to_rgs_valid_cargs%');
/** @var VerifyPKCS7Signature $verifyPKCS7Signature */
$verifyPKCS7Signature = LegacyObjectsManager::getLegacyObjectInstancier()->get(VerifyPKCS7Signature::class);

$valid = $verifyPKCS7Signature->verifySignature(
    file_get_contents($signature),
    $RGSCaPath,
    VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
    $file,
    $date
);

echo ($valid ? 'Valide' : 'Invalide') . PHP_EOL;
