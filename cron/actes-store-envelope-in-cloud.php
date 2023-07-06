#! /usr/bin/php
<?php

use S2lowLegacy\Class\actes\ActesStoreEnveloppeWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerRunnerWithDataFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerRunnerBuilder $workerBuilder */
[$workerBuilder,$worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,ActesStoreEnveloppeWorker::class]);

$workerBuilder->scriptWithLogs(
    $worker,
    true,
    WorkerRunnerWithDataFromDB::class
)->work();
