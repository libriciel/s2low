<?php

require_once(dirname(__FILE__) . "/../init/init.php");
/** @var LogsController $logsController */
$logsController = LegacyObjectsManager::getLegacyObjectInstancier()->{'LogsController'};
$logsController->vidange(KEEP_NB_MONTHS_IN_LOGS);

sleep(3600);
