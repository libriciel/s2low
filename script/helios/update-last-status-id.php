<?php

use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(__DIR__ . "/../../init/init.php");
$heliosTransactionsSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(HeliosTransactionsSQL::class);

$heliosTransactionsSQL->updateLastStatusId();
