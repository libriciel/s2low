<?php

require_once(__DIR__ . "/../init/init.php");


$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->scriptByClassName(ActesVerifSaeWorker::class, true, true);
