<?php

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(__DIR__ . '/../../init/init.php');

/** @var S2lowLogger $s2LowLogger */
/** @var HeliosTransactionsSQL $transactions_sql */
list($s2LowLogger, $transactions_sql) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([S2lowLogger::class, HeliosTransactionsSQL::class]);

$s2LowLogger->enableStdOut();

if ($argc != 3) {
    $s2LowLogger->error('Nombre de paramètres incorrect. ( 2 Attendus, ' . ($argc - 1) . ' renseigné(s) )');
    $s2LowLogger->error("Usage $argv[0] debut fin");
    $s2LowLogger->error("$argv[0] : Affiche toutes les infos des transactions au format transmis entre debut et fin");
    $s2LowLogger->error('Date au format YYYY-mm-dd');
    exit(-1);
}

$debut = $argv[1];
$fin =  $argv[2];

//$debut = "2016-04-26";
//$debut = "2008-04-26";
//$fin = "2016-05-04";


foreach ($transactions_sql->getTransactionsInfosByDateAndStatus(HeliosTransactionsSQL::TRANSMIS, $debut, $fin) as $transaction) {
    echo implode(";", $transaction);
    echo "\n";
}
