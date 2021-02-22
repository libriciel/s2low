<?php
//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal
require_once __DIR__."/../../init/init.php";


$filepath = '/var/www/s2low/test/TestPDF/Delib_LIBRICIEL.pdf';
if(isset($argv[1]))
    $filepath = $argv[1];

$padesValid = $objectInstancier->get('PadesValid');

if($padesValid->validate($filepath)){

    echo "La signature est valide $filepath\n";
    exit(0);
} else {
    echo "Le fichier $filepath n'a pas de signature\n";
}

exit(2);