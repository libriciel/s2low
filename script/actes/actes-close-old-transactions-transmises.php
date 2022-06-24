<?php

require_once __DIR__ . "/../../init/init.php";

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->setName("actes-close-old-transaction-transmise");
$s2lowLogger->enableStdOut(true);

$actesTransactionsCloser = $objectInstancier->get(ActesTransactionsCloser::class);
$actesTransactionsCloser->closeAll();
