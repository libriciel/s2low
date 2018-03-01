<?php

require_once __DIR__."/../init/init.php";
require_once __DIR__."/S2lowBootstrap.class.php";


/** @var ObjectInstancier $objectInstancier */
/** @var S2lowBootstrap $slowBootrap */
$slowBootrap = $objectInstancier->get('S2lowBootstrap');


$slowBootrap->bootstrap();
