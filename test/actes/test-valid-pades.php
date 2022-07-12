<?php

require_once __DIR__ . "/../../init/init.php";
$objectInstancier = LegacyObjectsManager::getLegacyObjectInstancier();


if ($argc < 2) {
    echo "Usage : {$argv[0]} fichier\nValide la signature PADES d'un fichier\n";
    return -1;
}
$filepath = $argv[1];

$padesValid = $objectInstancier->get("PadesValid");

try {
    $is_signed = $padesValid->validate($filepath);
} catch (Exception $e) {
    echo "Erreur lors de la validation de la signature du fichier : " . $e->getMessage() . "\n";
    echo $padesValid->getLastResult();
    return -2;
}
if ($is_signed) {
    echo "Le fichier est signé et la signature est valide\n";
    print_r($padesValid->getLastResult());
    return 0;
} else {
    echo "Le fichier n'est pas signé\n";
    echo $padesValid->getLastResult();
    return -3;
}
