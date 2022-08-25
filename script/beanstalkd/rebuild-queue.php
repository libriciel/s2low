<?php

use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\actes\ActesAnalyseFichierRecuWorker;
use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesEnvoiFichierWorker;
use S2lowLegacy\Class\actes\ActesEnvoiSaeWorker;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecuWorker;
use S2lowLegacy\Class\helios\HeliosEnvoiWorker;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;

require_once __DIR__ . "/../../init/init.php";
$objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier();

$all = [
    ActesAntivirusWorker::class,
    ActesAnalyseFichierAEnvoyerWorker::class,
    ActesEnvoiFichierWorker::class,
    ActesAnalyseFichierRecuWorker::class,
    ActesEnvoiSaeWorker::class,
    HeliosAnalyseFichierAEnvoyerWorker::class,
    HeliosEnvoiWorker::class,
    HeliosAnalyseFichierRecuWorker::class,
];


$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->enableStdOut();
$workerScript = $objectInstancier->get(WorkerScript::class);

foreach ($all as $workerClassname) {
    /** @var IWorker $worker */
    $worker = $objectInstancier->get($workerClassname);
    $s2lowLogger->setName($worker->getQueueName() . "-rebuild-queue");
    $workerScript->rebuildQueue($worker);
}
