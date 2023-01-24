#! /usr/bin/php
<?php

use S2lowLegacy\Class\helios\HeliosMenagePesRetourWorker;
use S2lowLegacy\Class\WorkerScript;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerScript $workerScript */
$workerScript = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);

$workerScript->scriptByClassName(
    HeliosMenagePesRetourWorker::class,
    true,
    true
);
