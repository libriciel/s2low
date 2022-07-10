<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once(__DIR__ . "/../config/config.php");

require_once(SITEROOT . '/class/include.class.php');

require_once(__DIR__ . "/../class/util.php");

//Cette variable est utilisée partout sans être initialisé...
$html = "";

$jsonOutput = new JSONoutput();

list($objectInstancier, $html, $jsonOutput,$sqlQuery, $frontController) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, 'html', JSONoutput::class, SQLQuery::class, FrontController::class]
    );
