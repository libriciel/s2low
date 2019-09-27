<?php
require_once( __DIR__ . "/../init/init.php");

/** @var LogsController $logsController */
$logsController = $objectInstancier->{'LogsController'};
$logsController->doRequest();



sleep(60);