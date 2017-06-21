<?php


require_once(__DIR__ . "/../init/init.php");

/** @var ScriptSleeping $scriptSleeping */
$scriptSleeping = $objectInstancier->get("ScriptSleeping");

$scriptSleeping->debut("helios-store-pes-aller-in-cloud");

/** @var PesAllerStorage $pesAllerStorage */
$pesAllerStorage = $objectInstancier->get("PesAllerStorage");
$pesAllerStorage->storeAll();

$scriptSleeping->fin();
