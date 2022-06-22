<?php

require_once(dirname(__FILE__) . "/../config/config.php");

/** @var LogsController $logsController */
$logsController = $objectInstancier->{'LogsController'};
$logsController->vidange(KEEP_NB_MONTHS_IN_LOGS);

sleep(3600);
