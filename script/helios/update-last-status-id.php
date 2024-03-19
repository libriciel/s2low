<?php

use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(__DIR__ . "/../../init/init.php");
/** @var HeliosTransactionsSQL $heliosTransactionsSQL */
$heliosTransactionsSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(HeliosTransactionsSQL::class);

$heliosTransactionsSQL->updateLastStatusId();
