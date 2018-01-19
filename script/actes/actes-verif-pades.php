<?php

require_once __DIR__."/../../init/init.php";

$filepath = $argv[1];

$padesValid = $objectInstancier->get('PadesValid');

if($padesValid->validate($filepath)){

    echo "La signature est valide\n";

} else {
    echo "Le fichier n'a pas de signature\n";
}

