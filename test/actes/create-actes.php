<?php

use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\LegacyObjectsManager;

require_once(__DIR__ . "/../../init/init.php");
require_once __DIR__ . "/../PHPUnit/class/actes/ActesCreator.php";
list($actesCreator,$actes_files_upload_root) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([ActesCreator::class,'actes_files_upload_root']);

$transaction_id = $actesCreator->createTransaction(
    ActesStatusSQL::STATUS_POSTE,
    __DIR__ . "/../PHPUnit/class/actes/fixtures/abc-TACT--000000000--20170803-16.tar.gz",
    $actes_files_upload_root
);
