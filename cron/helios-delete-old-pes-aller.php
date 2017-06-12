<?php


if (isset($argv[1])){
    $nb_days = $argv[1];
} else {
    $nb_days = 99999;
}

require_once(__DIR__ . "/../init/init.php");

/** @var ScriptSleeping $scriptSleeping */
$scriptSleeping = $objectInstancier->get("ScriptSleeping");

$scriptSleeping->debut("helios-delete-old-pes-aller");

/** @var PesAllerStorage $pesAllerStorage */
$pesAllerStorage = $objectInstancier->get("PesAllerStorage");
$pesAllerStorage->menageLocal($nb_days);

$scriptSleeping->fin();
