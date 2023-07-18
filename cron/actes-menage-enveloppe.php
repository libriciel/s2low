<?php

use S2lowLegacy\Class\actes\ActesMenageEnveloppeWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use S2lowLegacy\Class\WorkerRunnerWithDataFromDB;

require_once(__DIR__ . '/../init/init.php');
/** @var WorkerRunnerBuilder $workerBuilder */
/** @var ActesMenageEnveloppeWorker $worker */
[$workerBuilder, $worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,ActesMenageEnveloppeWorker::class]);

$workerBuilder->scriptWithLogs(
    $worker,
    true,
    WorkerRunnerWithDataFromDB::class
)->work();
