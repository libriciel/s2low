<?php

require_once(__DIR__."/../../class/VerifyPKCS7Signature.class.php");

$file_path = "/Users/eric/Desktop/test/034-123456725-20151201-TESTS132-AU-1-1_1.pdf";

$file_manifest_path = "/Users/eric/Desktop/test/034-123456725-20151201-TESTS132-AU-1-1_0.xml";

$dom = simplexml_load_file($file_manifest_path);

$namespaces = $dom->getDocNamespaces();
// Récupération des éléments dans le namespace "actes"
$actesItems = $dom->children($namespaces["actes"]);


$signature =  $actesItems->Document->Signature . "\n";


$verifyPKCS7Signature = new VerifyPKCS7SIgnature("/etc/tedetis/ssl/validca/");
$verifyPKCS7Signature->verifyCertificate($signature);
