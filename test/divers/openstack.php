<?php

require_once(__DIR__ . "/../../init/init.php");

$file_content = mt_rand(0, mt_getrandmax());
$filename = tempnam(sys_get_temp_dir(), "test_s2low_openstack_");
echo "Création du fichier $filename\n";
file_put_contents($filename, $file_content);

/** @var OpenStackSwiftWrapper $openStackSwiftWrapper */
$openStackSwiftWrapper = $objectInstancier->get("OpenStackSwiftWrapper");


$debut = microtime(true) . "\n";
$openStackSwiftWrapper->sendFile("test", $filename);
echo "Temps pour envoyer un fichier : ";
echo ceil((microtime(true) - $debut) * 1000);
echo "ms\n";

unlink($filename);

$debut = microtime(true) . "\n";
$openStackSwiftWrapper->retrieveFile("test", $filename);
echo "Temps pour récupérer un fichier (swift): ";
echo ceil((microtime(true) - $debut) * 1000);
echo "ms\n";

$debut = microtime(true) . "\n";
$file_content_result = file_get_contents($filename);
echo "Temps pour récupérer un fichier (local): ";
echo ceil((microtime(true) - $debut) * 1000);
echo "ms\n";


if ($file_content != $file_content_result) {
    throw new Exception("Probleme...");
}

unlink($filename);
$openStackSwiftWrapper->deleteFile("test", basename($filename));
