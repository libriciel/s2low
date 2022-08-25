<?php

use S2lowLegacy\Class\actes\ActesTransactionsCloser;
use S2lowLegacy\Class\S2lowLogger;

require_once __DIR__ . "/../../init/init.php";
list($s2lowLogger, $actesTransactionsCloser) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([S2lowLogger::class,ActesTransactionsCloser::class]);

$s2lowLogger->setName("actes-close-old-transaction-transmise");
$s2lowLogger->enableStdOut(true);

$actesTransactionsCloser->closeAll();
