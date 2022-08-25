<?php

use S2lowLegacy\Class\XSDValidation;

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

$file_path = $argv[1];

$xsdValidation = new XSDValidation(__DIR__ . "/../../xsd/actesv1_1.xsd");


$v = $xsdValidation->validate(file_get_contents($file_path));

echo $v ? "OK" : "FAILED";

echo "\n";
