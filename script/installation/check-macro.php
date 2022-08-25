<?php

// Ce script a pour but de retourner les macro qui n'ont pas été surchargées

require_once(__DIR__ . "/../../config/LoadLocalSettings.php");
echo "récupération des constantes surchargées";
$constantes_surchargee = get_defined_constants();

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

echo "récupération de toutes les constantes";
$constantes_globales = get_defined_constants();

$constantes_non_surchargees = array_diff_assoc($constantes_globales, $constantes_surchargee);

echo "Voici les marcos qui n'ont pas été surchargées";
print_r($constantes_non_surchargees);
