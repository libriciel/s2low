<?php

require_once(__DIR__ . "/../../init/init.php");
LegacyObjectsManager::setLegacyObjectInstancier();

$heliosPESValidation = new HeliosPESValidation(HELIOS_XSD_PATH);

$pes_content = file_get_contents($argv[1]);

$r = $heliosPESValidation->validate($pes_content);

echo $r ? "OK" : "FAILED";
echo "\n";


print_r($heliosPESValidation->getLastError());
