<?php

use S2lowLegacy\Class\AcCertificatesRetrieverWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerRunnerWithDataFromDB;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use S2lowLegacy\Class\WorkerScript;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerRunnerBuilder $workerBuilder */
[$workerBuilder,$worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,AcCertificatesRetrieverWorker::class]);

$worker = $workerBuilder->scriptWithLogs($worker, true, WorkerRunnerWithDataFromDB::class);
$worker->setMinExecutionTimeInSeconds(3600);
$worker->work();
