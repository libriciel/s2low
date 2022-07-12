<?php

require_once __DIR__ . "/../../init/init.php";
list($s2lowLogger, $actesTransactionsCloser) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([S2lowLogger::class,ActesTransactionsCloser::class]);

$s2lowLogger->setName("actes-close-old-transaction-transmise");
$s2lowLogger->enableStdOut(true);

$actesTransactionsCloser->closeAll();
