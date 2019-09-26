<?php

require_once __DIR__."/../../init/init.php";

$all = [
	ActesAntivirusWorker::class,
	ActesAnalyseFichierAEnvoyerWorker::class,
	ActesEnvoiFichierWorker::class,
	ActesEnvoiSaeWorker::class,
	HeliosStorePESAllerWorker::class
];


$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->enableStdOut();
$workerScript = $objectInstancier->get(WorkerScript::class);

foreach($all as $workerClassname) {
	/** @var IWorker $worker */
	$worker = $objectInstancier->get($workerClassname);
	$s2lowLogger->setName($worker->getQueueName() . "-rebuild-queue");
	$workerScript->rebuildQueue($worker);
}
