<?php

require_once __DIR__ . "/../init/init.php";
$slowBootrap = LegacyObjectsManager::getLegacyObjectInstancier()->get(S2lowBootstrap::class);

$slowBootrap->bootstrap();
