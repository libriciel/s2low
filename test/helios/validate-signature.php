<?php


require_once(__DIR__."/../../init/init.php");


$xml_file = $argv[1];

echo "Analyse du fichier : $xml_file\n";

$xadesSignature = new XadesSignature(XMLSEC1_PATH,new PKCS12(),new X509Certificate());

echo $xadesSignature->verifyNoCA($xml_file);

echo "\n";