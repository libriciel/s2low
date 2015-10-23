<?php

require_once(__DIR__."/../../class/VerifyPKCS7Signature.class.php");

$file_path = "/Users/eric/Desktop/001-862614864-20141201-20141203A-AR-1-1_1.pdf";

$file_manifest_path = "/Users/eric/Desktop/001-862614864-20141201-20141203A-AR-1-1_0.xml";

$dom = simplexml_load_file($file_manifest_path);

$namespaces = $dom->getDocNamespaces();
// Récupération des éléments dans le namespace "actes"
$actesItems = $dom->children($namespaces["actes"]);


$signature =  $actesItems->Document->Signature . "\n";


$verifyPKCS7Signature = new VerifyPKCS7SIgnature("/etc/tedetis/ssl/validca/");
$verifyPKCS7Signature->verify($file_path, $signature);
