<?php

use S2lowLegacy\Class\helios\FichierCompteur;

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

$fc = new FichierCompteur("/tmp/fichier_compteur.txt");

echo $fc->getNumero() . "\n";
