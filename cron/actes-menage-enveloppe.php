<?php

use S2lowLegacy\Class\GenericMenageWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerRunnerBuilder;
use S2lowLegacy\Class\JobFetcherFromDB;

require_once(__DIR__ . '/../init/init.php');
/** @var WorkerRunnerBuilder $workerBuilder */
/** @var GenericMenageWorker $worker */
[$workerBuilder, $worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,'actes.menageEnveloppeWorker']);

$workerBuilder->scriptWithLogs(
    $worker,
    true,
    JobFetcherFromDB::class
)->work();
