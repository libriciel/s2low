#! /usr/bin/php
<?php

use S2lowLegacy\Class\helios\HeliosStorePESAcquitWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use S2lowLegacy\Class\WorkerScript;

require_once(__DIR__ . "/../init/init.php");
/** @var \S2lowLegacy\Class\WorkerRunnerBuilder $workerBuilder */
[$workerBuilder,$worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,HeliosStorePESAcquitWorker::class]);

$workerBuilder->scriptWithLogs(
    $worker,
    true,
    \S2lowLegacy\Class\WorkerRunnerWithDataFromDB::class
)->work();
