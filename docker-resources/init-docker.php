<?php

require_once __DIR__."/../init/init.php";
require_once __DIR__."/S2lowBootstrap.class.php";

$slowBootrap = $objectInstancier->get(S2lowBootstrap::class);
$slowBootrap->bootstrap();
