<?php

require_once(__DIR__ . "/../init/init.php");
$objectInstancier = LegacyObjectsManager::getLegacyObjectInstancier();

/** @var LogsController $logsController */
$logsController = $objectInstancier->{'LogsController'};
$logsController->doRequest();



sleep(60);
