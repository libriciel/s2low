#! /usr/bin/php
<?php

use S2lowLegacy\Class\helios\HeliosEnvoiSaeWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\JobFetcherFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerRunnerBuilder $workerBuilder */
[$workerBuilder,$worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,HeliosEnvoiSaeWorker::class]);

$workerBuilder->scriptWithLogs($worker, true, JobFetcherFromDB::class)->work();
