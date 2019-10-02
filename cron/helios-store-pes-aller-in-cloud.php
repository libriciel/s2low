#! /usr/bin/php
<?php
require_once( __DIR__ . "/../init/init.php");

$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->scriptByClassName(
        HeliosStorePESAllerWorker::class,
        true,
        true
);

