<?php

/*
 * Ce script permet d'envoyer une demande de classification pour TOUTES les collectivites utilisant le module actes.
 * Il est aussi possible de specifier un departement. cela enverra une demande de classification pour TOUTES
 * les collectivites presentes dans ce departement.
 */

require_once __DIR__ . "/../../init/init.php";
LegacyObjectsManager::setLegacyObjectInstancier();

if ($argc == 2 && ((int)$argv[1] == 0)) {
    echo "Usage {$argv[0]} [departement]\n";
    echo "{$argv[0]} : permet d'envoyer une demande de classification a toutes les collectivites utilisant le module actes.\n
    Si le departement est passe en arguement (optionnel), cela enverra une demande de classificaiton pour toutes les collectivite
    utilisant le module acte et presente dans ce departement.\n";
    exit(-1);
}

$departement = null;

if (
    $argc == 2
    && (
        (((int)$argv[1]) < 0) || ((int)$argv[1] > 1000)
        )
) {
    echo "Le departement " . (int)$argv[1] . " doit etre compris entre 0 et 1000\n";
    exit(-1);
} elseif (
    $argc == 2
        &&
        ((int)$argv[1] > 0) && ((int)$argv[1] < 1000)
) {
    $actesClassificationCreation = new ActesClassificationCreation();
    $actesClassificationCreation->sendToAllAuthorities($departement);
}
