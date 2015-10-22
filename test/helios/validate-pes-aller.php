<?php

require_once("../../class/helios/HeliosPESValidation.class.php");

$heliosPESValidation = new HeliosPESValidation(__DIR__."/../../xsd/Schemas_PES_v471_072015/");

$pes_content = file_get_contents($argv[1]);

$r = $heliosPESValidation->validate($pes_content);

echo $r?"OK":"FAILED";
echo "\n";


print_r($heliosPESValidation->getLastError());