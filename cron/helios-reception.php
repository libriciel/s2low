#! /usr/bin/php
<?php
require_once (__DIR__."/../config/config.php");

$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->scriptByClassName(HeliosReceptionWorker::class);


