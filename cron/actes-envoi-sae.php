#! /usr/bin/php
<?php
require_once(__DIR__ . "/../init/init.php");
$workerScript = LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);

$workerScript->scriptByClassName(ActesEnvoiSaeWorker::class, true, true);
