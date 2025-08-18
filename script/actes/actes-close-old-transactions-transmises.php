<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\actes\ActesTransactionsCloser;

require_once __DIR__ . "/../../init/init.php";
list($s2lowLogger, $actesTransactionsCloser) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([LoggerInterface::class,ActesTransactionsCloser::class]);

$actesTransactionsCloser->closeAll();
