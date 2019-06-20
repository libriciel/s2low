<?php

declare(ticks = 1);


require_once(__DIR__ . "/../init/init.php");

/** @var ScriptSleeping $scriptSleeping */
$scriptSleeping = $objectInstancier->get("ScriptSleeping");

$scriptSleeping->debut("helios-store-pes-aller-in-cloud");

$logger  = $objectInstancier->get("Monolog\Logger");
$logger->pushHandler(new  Monolog\Handler\StreamHandler('php://stdout'));

$logger->info("Starting helios-store-envelope-in-cloud");


/** @var PesAllerStorage $pesAllerStorage */
$pesAllerStorage = $objectInstancier->get("PesAllerStorage");
$pesAllerStorage->storeAll();
$logger->info("Ending actes-store-envelope-in-cloud");
$scriptSleeping->fin();
exit(0);

