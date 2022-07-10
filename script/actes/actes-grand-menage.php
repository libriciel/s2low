<?php

require_once(__DIR__ . "/../../init/init.php");

$objectInstancier->set("Monolog\Logger", $logger->withName('ACTES-GRAND-MENAGE'));


if (empty($argv[1])) {
    $min_date = '1969-12-31';
} else {
    $min_date = $argv[1];
}


if (isset($argv[2])) {
    $max_date = $argv[2];
} else {
    $max_date = '1969-12-31';
}


if (empty($argv[3]) || $argv[3] != 'ok') {
    $confirm = false;
} else {
    $confirm = true;
}

/** @var ScriptSleeping $scriptSleeping */
$scriptSleeping = $objectInstancier->get("ScriptSleeping");

$scriptSleeping->debut(" actes-grand-menage");

$logger  = $objectInstancier->get("Monolog\Logger");
$logger->pushHandler(new  Monolog\Handler\StreamHandler('php://stdout'));

$logger->info("Starting actes-grand-menage");
/** @var ActesEnvelopeStorage $actesEnvelopeStorage */
$actesEnvelopeStorage = $objectInstancier->get(ActesEnvelopeStorage::class);
try {
    $actesEnvelopeStorage->grandMenage($min_date, $max_date, $confirm);
} catch (Exception $e) {
    $logger->critical("Exception thrown during actes-grand-menage", $e);
    exit(-1);
}
$logger->info("Ending actes-grand-menage");
$scriptSleeping->fin();
exit(0);
