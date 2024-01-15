<?php

use S2lowLegacy\Class\actes\ActesStatusSQL;

require_once(__DIR__ . "/../../init/init.php");
require_once __DIR__ . "/../PHPUnit/class/actes/ActesCreator.php";

/** @var \ActesCreator $actesCreator */
$actesCreator = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(ActesCreator::class);

$transaction_id = $actesCreator->createTransaction(
    ActesStatusSQL::STATUS_POSTE,
    __DIR__ . "/../PHPUnit/class/actes/fixtures/abc-TACT--000000000--20170803-16.tar.gz",
    ACTES_FILES_UPLOAD_ROOT
);
