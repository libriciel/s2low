<?php

use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;

$_GET['api'] = 1;
LegacyObjectsManager::getLegacyObjectInstancier()->get(Initialisation::class)->doInit();

echo 'OK';
