<?php

use S2lowLegacy\Controller\LogsController;

require_once(dirname(__FILE__) . "/../init/init.php");

/** @var LogsController $logsController */
$logsController = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(LogsController::class);
$logsController->vidange(KEEP_NB_MONTHS_IN_LOGS);

sleep(3600);
