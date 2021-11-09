<?php
require_once( __DIR__ . "/../init/init.php");


$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->setMinExecutionTimeInSeconds(300);
$workerScript->scriptByClassName(AcCertificatesRetrieverWorker::class,true,true);