<?php

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Model\HeliosTransactionsSQL;

require_once(__DIR__ . '/../../init/init.php');
/** @var HeliosTransactionsSQL $heliosTransactionsSQL */
$heliosTransactionsSQL = LegacyObjectsManager::getLegacyObjectInstancier()
    ->get(HeliosTransactionsSQL::class);

$heliosTransactionsSQL->updateLastStatusId();
