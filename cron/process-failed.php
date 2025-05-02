<?php

// include the composer autoloader
require_once __DIR__ . "/../init/init.php";
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();


use Mtdowling\Supervisor\EventListener;
use Mtdowling\Supervisor\EventNotification;
use Psr\Log\LoggerInterface;

$listener = new EventListener();
$listener->listen(function (EventListener $listener, EventNotification $event) {
    $objectInstancier = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
    $logger = $objectInstancier->get(LoggerInterface::class);
    $eventData = $event->getData();
    if (isset($eventData['processname'])) {
        $processname = $eventData['processname'];
    } else {
        $processname = "unknow";
    }
    $logger->critical("SUPERVISORD process $processname enter FATAL state !", $eventData);
    return true;
});
