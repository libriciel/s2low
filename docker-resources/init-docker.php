<?php

require_once __DIR__ . "/../init/init.php";
LegacyObjectsManager::getLegacyObjectInstancier()->get(S2lowBootstrap::class);

$slowBootrap->bootstrap();
