<?php
require_once( __DIR__."/../../init/init.php");

$heliosController = new HeliosController($objectInstancier);
$heliosController->updateSiretFromPESAller();