<?php

use S2lowLegacy\Class\Antivirus;

require_once(__DIR__ . "/../init/init.php");
list($antivirus) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [Antivirus::class]
    );


if ($argc < 2) {
    echo "{$argv[0]} : valide un fichier avec l'antivirus\n";
    echo "Usage : {$argv[0]} file_path\n";
    exit(-1);
}

$file_path = $argv[1];

$antivirus->checkArchiveSanity($file_path);
