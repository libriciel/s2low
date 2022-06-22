<?php

require_once(__DIR__ . "/../init/init.php");


if ($argc < 2) {
    echo "{$argv[0]} : valide un fichier avec l'antivirus\n";
    echo "Usage : {$argv[0]} file_path\n";
    exit(-1);
}

$file_path = $argv[1];

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->enableStdOut();

$antivirus = $objectInstancier->get(Antivirus::class);
$antivirus->checkArchiveSanity($file_path);
