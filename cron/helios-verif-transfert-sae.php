<?php

use S2lowLegacy\Class\helios\HeliosVerificationSaeWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerRunnerBuilder;

require_once(__DIR__ . "/../init/init.php");
/** @var \S2lowLegacy\Class\WorkerRunnerBuilder $workerBuilder */
[$workerBuilder,$worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,HeliosVerificationSaeWorker::class]);

$workerBuilder->scriptWithLogs(
    $worker,
    true,
    \S2lowLegacy\Class\WorkerRunnerWithDataFromDB::class
)->work();
