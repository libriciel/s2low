<?php

require_once __DIR__."/../init/init.php";
require_once __DIR__."/S2lowBootstrap.class.php";

$slowBootrap = new S2lowBootstrap();
$slowBootrap->bootstrap($sqlQuery);
