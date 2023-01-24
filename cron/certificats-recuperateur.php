<?php

use S2lowLegacy\Class\AcCertificatesRetrieverWorker;
use S2lowLegacy\Class\WorkerScript;

require_once(__DIR__ . "/../init/init.php");
/** @var WorkerScript $workerScript */
$workerScript = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);

$workerScript->setMinExecutionTimeInSeconds(3600);
$workerScript->scriptByClassName(AcCertificatesRetrieverWorker::class, true, true);
