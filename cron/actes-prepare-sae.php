<?php

use S2lowLegacy\Class\actes\ActesPrepareSaeWorker;
use S2lowLegacy\Class\WorkerScript;

require_once(__DIR__ . "/../init/init.php");
$workerScript = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);

$workerScript->scriptByClassName(ActesPrepareSaeWorker::class, true, true);
