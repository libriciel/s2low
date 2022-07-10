<?php

require_once(__DIR__ . "/../../init/init.php");
list($s2lowBootstrap, $s2lowLogger ) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [S2lowLogger::class,S2lowBootstrap::class]
    );

$s2lowLogger->enableStdOut(true);
$s2lowBootstrap->installHorodateur();
