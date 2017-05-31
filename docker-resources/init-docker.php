<?php

require_once __DIR__."/../init/init.php";
require_once __DIR__."/S2lowBootstrap.class.php";

echo "Database hostname : ".DB_HOST_TEST."\n";
echo "Database name : ".DB_DATABASE_TEST."\n";
echo "Database name : ".DB_USER_TEST."\n";


$slowBootrap = new S2lowBootstrap();
$slowBootrap->bootstrap($sqlQuery);
