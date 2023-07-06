#! /usr/bin/php
<?php

use S2lowLegacy\Class\actes\ActesReceptionFichierWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerRunnerWithDataFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerRunnerBuilder $workerBuilder */
[$workerBuilder,$worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,ActesReceptionFichierWorker::class]);

$worker = $workerBuilder->scriptWithLogs($worker, true, WorkerRunnerWithDataFromDB::class);
$worker->setMinExecutionTimeInSeconds(10);
$worker->work();
