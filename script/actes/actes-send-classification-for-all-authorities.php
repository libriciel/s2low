<?php
declare(ticks = 1);

/*
 * Ce script permet d'envoyer une demande de classification pour TOUTES les collectivités utilisant le module actes.
 */

require_once __DIR__."/../../init/init.php";

$actesClassificationCreation = new ActesClassificationCreation();
$actesClassificationCreation->sendToAllAuthorities();