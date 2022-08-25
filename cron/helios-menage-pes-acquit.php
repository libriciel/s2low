#! /usr/bin/php
<?php

use S2lowLegacy\Class\helios\HeliosMenagePesAcquitWorker;
use S2lowLegacy\Class\WorkerScript;

require_once(__DIR__ . "/../init/init.php");
$workerScript = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);

$workerScript->scriptByClassName(
    HeliosMenagePesAcquitWorker::class,
    true,
    true
);
