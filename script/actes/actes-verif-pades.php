<?php

//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal
use S2lowLegacy\Class\PadesValid;

require_once __DIR__ . "/../../init/init.php";
$objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier();


$filepath = '/var/www/s2low/test/TestPDF/Delib_LIBRICIEL.pdf';
if (isset($argv[1])) {
    $filepath = $argv[1];
}

$padesValid = $objectInstancier->get(PadesValid::class);

if ($padesValid->validate($filepath)) {
    echo "La signature est valide $filepath\n";
    exit(0);
} else {
    echo "Le fichier $filepath n'a pas de signature\n";
}

exit(2);
