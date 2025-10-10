<?php

use S2low\Services\CertificateStores\Stores;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\VerifyPemCertificateFactory;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\PKCS12;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Lib\XadesSignature;
use S2lowLegacy\Lib\XadesSignatureParser;
use S2lowLegacy\Lib\XadesSignatureProperties;

require_once(__DIR__ . "/../../init/init.php");
LegacyObjectsManager::setLegacyObjectInstancier();
/** @var Stores $certificatesStores */
$certificatesStores = LegacyObjectsManager::getLegacyObjectInstancier()->get(Stores::class);

if (empty($argv[2])) {
    echo "Usage : {$argv[0]} entree.xml sortie.xml\n";
    exit;
}

$xml_file = $argv[1];
$output = $argv[2];

echo "Signature du fichier : $xml_file\n";

$xadesSignature = new XadesSignature(
    XMLSEC1_PATH,
    new PKCS12(),
    new X509Certificate(),
    $certificatesStores->getDefaultStorePath(),
    new XadesSignatureParser(),
    new PemCertificateFactory(),
    (new VerifyPemCertificateFactory())->get($certificatesStores->getDefaultStorePath())
);

$xadesSignatureProperties = new XadesSignatureProperties();
$xadesSignatureProperties->city = "Paris";
$xadesSignatureProperties->postalCode = "75008";
$xadesSignatureProperties->countryName = "France";
$xadesSignatureProperties->claimedRole = "Test Tiers de télétransmission";

$xadesSignature->sign(
    $xml_file,
    __DIR__ . '/../../data-exemple/plateforme-cert.p12',
    'robert_petitpoids',
    $output,
    $xadesSignatureProperties
);

echo "fichier signé\n";
