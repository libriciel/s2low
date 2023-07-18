<?php

use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;

$_GET['api'] = 1;
/** @var Initialisation $init */
$init = LegacyObjectsManager::getLegacyObjectInstancier()->get(Initialisation::class);
$init->init();

echo "OK";
