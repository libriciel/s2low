<?php

require_once(__DIR__ . "/../../init/init.php");

$fc = new FichierCompteur("/tmp/fichier_compteur.txt");

echo $fc->getNumero() . "\n";
