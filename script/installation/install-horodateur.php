<?php

use S2lowLegacy\Boot\S2lowBootstrap;
use S2lowLegacy\Class\S2lowLogger;

require_once(__DIR__ . "/../../init/init.php");
list($s2lowBootstrap, $s2lowLogger ) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [S2lowLogger::class,S2lowBootstrap::class]
    );

$s2lowLogger->enableStdOut(true);
$s2lowBootstrap->installHorodateur();
