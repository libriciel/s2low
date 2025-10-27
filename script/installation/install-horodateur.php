<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Boot\S2lowBootstrap;

require_once(__DIR__ . "/../../init/init.php");
list($s2lowBootstrap, $s2lowLogger ) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [LoggerInterface::class,S2lowBootstrap::class]
    );

$s2lowBootstrap->installHorodateur();
