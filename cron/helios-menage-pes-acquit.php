#! /usr/bin/php
<?php

use S2lowLegacy\Class\helios\HeliosMenagePesAcquitWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\JobFetcherFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerRunnerBuilder $workerBuilder */
[$workerBuilder,$worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,HeliosMenagePesAcquitWorker::class]);

$workerBuilder->scriptWithLogs(
    $worker,
    true,
    JobFetcherFromDB::class
)->work();
