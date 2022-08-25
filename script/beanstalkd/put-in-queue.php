<?php

use S2lowLegacy\Class\BeanstalkdWrapper;

require_once __DIR__ . "/../../init/init.php";
$objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier();


if ($argc < 3) {
    echo "Usage: {$argv[0]} queue_name od_to_send\n";
    exit(-1);
}

$queue_name = $argv[1];
$id = $argv[2];

$objectInstancier->get(BeanstalkdWrapper::class)->put($queue_name, $id);
