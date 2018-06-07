<?php

require_once __DIR__."/../../init/init.php";

$actesAntivirus = $objectInstancier->get(ActesAntivirusWorker::class);
$workerScript = $objectInstancier->get(WorkerScript::class);
$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->setName($actesAntivirus->getQueueName()."-rebuild-queue");
$s2lowLogger->enableStdOut();

$workerScript->rebuildQueue($actesAntivirus);
