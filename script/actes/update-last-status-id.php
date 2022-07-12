<?php

require_once(__DIR__ . "/../../init/init.php");
$actesTransactionsSQL = LegacyObjectsManager::getLegacyObjectInstancier()->get(ActesTransactionsSQL::class);

$actesTransactionsSQL->updateLastStatusId();
