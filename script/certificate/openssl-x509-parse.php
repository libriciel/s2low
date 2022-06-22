<?php

require_once(__DIR__ . "/../../init/init.php");

$filepath = $argv[1];

$x509Certificate = new X509Certificate();

try {
    print_r($x509Certificate->getInfo(file_get_contents($filepath)));
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    print_r($e->getTrace());
}
