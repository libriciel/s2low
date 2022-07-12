#! /usr/bin/php
<?php
require_once(__DIR__ . "/../init/init.php");
$workerScript = LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);

$workerScript->setMinExecutionTimeInSeconds(10);
$workerScript->scriptByClassName(HeliosMenageWorker::class, true, true);
