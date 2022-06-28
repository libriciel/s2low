<?php

require_once __DIR__ . "/../init/init.php";

$slowBootrap = $objectInstancier->get(S2lowBootstrap::class);
$slowBootrap->bootstrap();
