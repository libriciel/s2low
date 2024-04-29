#! /usr/bin/php
<?php

use S2lowLegacy\Class\helios\HeliosMenagePesRetourWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\JobFetcherFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;

require_once(__DIR__ . "/../init/init.php");
/** @var \S2lowLegacy\Class\WorkerRunnerBuilder $workerBuilder */
[$workerBuilder,$worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,HeliosMenagePesRetourWorker::class]);

$workerBuilder->scriptWithLogs(
    $worker,
    true,
    JobFetcherFromDB::class
)->work();
