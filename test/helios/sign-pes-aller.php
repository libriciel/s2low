<?php

require_once(__DIR__."/../../init/init.php");

if (empty($argv[2])){
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
    EXTENDED_VALIDCA_PATH,
    new XadesSignatureParser(),
    new PemCertificateFactory()
);

$xadesSignatureProperties = new XadesSignatureProperties();
$xadesSignatureProperties->city = "Paris";
$xadesSignatureProperties->postalCode = "75008";
$xadesSignatureProperties->countryName = "France";
$xadesSignatureProperties->claimedRole = "Test Tiers de télétransmission";

$xadesSignature->sign(
	$xml_file,
	HELIOS_PLATEFORME_CERTIFICATE_P12,
	HELIOS_PLATEFORME_CERTIFICATE_PASSWORD,
	$output,
	$xadesSignatureProperties
);

echo "fichier signé\n";
