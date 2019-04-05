<?php
declare(ticks = 1);

/*
 * Ce script permet d'envoyer une demande de classification pour TOUTES les collectivités utilisant le module actes.
 * Il est aussi possible de spécifier un département. cela enverra une demande de classification pour TOUTES
 * les collectivités présentes dans ce département.
 */

require_once __DIR__."/../../init/init.php";

if ($argc == 2 && ((int)$argv[1] == 0) ){
    echo "Usage {$argv[0]} [departement]\n";
    echo "{$argv[0]} : permet d'envoyer une demande de classification à toutes les collectivités utilisant le module actes.\n
    Si le departement est passé en arguement (optionnel), cela enverra une demande de classificaiton pour toutes les collectivité
    utilisant le module acte et présente dans ce département.\n";
    exit(-1);
}

$departement=null;

if ($argc == 2
    && (
        (((int)$argv[1]) < 0) || ((int)$argv[1] > 1000)
        )
    )
{
    echo "Le département ".(int)$argv[1]." doit être compris entre 0 et 1000\n";
    exit(-1);
}elseif($argc == 2 && (!((int)$argv[1] > 0)) && (!((int)$argv[1] < 1000))){
    $departement=(int)$argv[1];
}

$actesClassificationCreation = new ActesClassificationCreation();
$actesClassificationCreation->sendToAllAuthorities($departement);
