<?php

use S2lowLegacy\Class\actes\ActesTransactionsSQL;

require_once(__DIR__ . "/../../init/init.php");
$actesTransactionsSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(ActesTransactionsSQL::class);

$actesTransactionsSQL->updateLastStatusId();
