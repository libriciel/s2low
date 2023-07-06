#! /usr/bin/php
<?php

use S2lowLegacy\Class\actes\ActesAnalyseFichierRecuWorker;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\WorkerRunnerBuilder;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerRunnerBuilder $workerBuilder */
[$workerBuilder,$worker] = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([WorkerRunnerBuilder::class,ActesAnalyseFichierRecuWorker::class]);

$workerBuilder->scriptWithLogs($worker)->work();
