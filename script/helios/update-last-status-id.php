<?php

require_once(__DIR__ . "/../../init/init.php");
$heliosTransactionsSQL = LegacyObjectsManager::getLegacyObjectInstancier()->get(HeliosTransactionsSQL::class);

$heliosTransactionsSQL->updateLastStatusId();
