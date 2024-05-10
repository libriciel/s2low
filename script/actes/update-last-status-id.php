<?php

use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\LegacyObjectsManager;

require_once(__DIR__ . '/../../init/init.php');
/** @var ActesTransactionsSQL $actesTransactionsSQL */
$actesTransactionsSQL = LegacyObjectsManager::getLegacyObjectInstancier()->get(ActesTransactionsSQL::class);

$actesTransactionsSQL->updateLastStatusId();
