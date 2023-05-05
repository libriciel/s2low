<?php

use S2lowLegacy\Class\actes\ActesMenageEnveloppeWorker;
use S2lowLegacy\Class\WorkerScript;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerScript $workerScript */
$workerScript = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);

$workerScript->scriptByClassName(
    ActesMenageEnveloppeWorker::class,
    true,
    true
);
