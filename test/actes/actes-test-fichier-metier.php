<?php

use Libriciel\LibActes\ActesXSD;
use S2lowLegacy\Class\XSDValidation;

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

$file_path = $argv[1];

$xsdValidation = new XSDValidation(ActesXSD::getCurrentXSDPath());


$v = $xsdValidation->validate(file_get_contents($file_path));

echo $v ? "OK" : "FAILED";

echo "\n";
