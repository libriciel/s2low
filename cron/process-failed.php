<?php

// include the composer autoloader
require_once __DIR__."/../init/init.php";


use Mtdowling\Supervisor\EventListener;
use Mtdowling\Supervisor\EventNotification;

$listener = new EventListener();
$listener->listen(function(EventListener $listener, EventNotification $event) {
	$objectInstancier = ObjectInstancierFactory::getObjetInstancier();
	$logger = $objectInstancier->get('Monolog\Logger');
	$eventData = $event->getData();
	if (isset($eventData['processname'])){
		$processname = $eventData['processname'];
	} else {
		$processname = "unknow";
	}
	$logger->critical("SUPERVISORD process $processname enter FATAL state !",$eventData);
	return true;
});
