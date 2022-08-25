<?php

require_once __DIR__ . "/../init/init.php";
$slowBootrap = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(S2lowBootstrap::class);

$slowBootrap->bootstrap();
