<?php

declare(ticks = 1);

require_once(__DIR__ . "/../init/init.php");

/** @var ScriptSleeping $scriptSleeping */
$scriptSleeping = $objectInstancier->get("ScriptSleeping");

$scriptSleeping->debut("actes-store-envelope-in-cloud");

$logger  = $objectInstancier->get("Monolog\Logger");
$logger->pushHandler(new  Monolog\Handler\StreamHandler('php://stdout'));

$logger->info("Starting actes-store-envelope-in-cloud");
/** @var ActesEnvelopeStorage $actesEnvelopeStorage */
$actesEnvelopeStorage = $objectInstancier->get(ActesEnvelopeStorage::class);
try {
	$actesEnvelopeStorage->storeAll();
} catch (Exception $e){
	$logger->critical("Exception thrown during actes-store-envelope-in-cloud : ".$e->getMessage(),[$e->getMessage(),$e->getTrace()]);
	exit(-1);
}
$logger->info("Ending actes-store-envelope-in-cloud");
$scriptSleeping->fin();
exit(0);
