<?php

use S2lowLegacy\Class\actes\ActesMenageEnveloppeWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerRunnerBuilder;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerRunnerBuilder $workerBuilder */
[$workerBuilder, $worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,ActesMenageEnveloppeWorker::class]);

$workerBuilder->scriptWithLogs(
    $worker,
    true,
    \S2lowLegacy\Class\WorkerRunnerWithDataFromDB::class
);
