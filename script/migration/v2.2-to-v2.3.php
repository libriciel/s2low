<?php

require_once(__DIR__ . "/../../init/init.php");


$min_id = 0;

$heliosController = new HeliosController($objectInstancier);
$heliosController->updateSiretFromPESAller($min_id);
