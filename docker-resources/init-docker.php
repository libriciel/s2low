<?php

require_once __DIR__."/../init/init.php";
require_once __DIR__."/S2lowBootstrap.class.php";

echo "Database hostname : ".DB_HOST."\n";
echo "Database name : ".DB_DATABASE."\n";
echo "Database name : ".DB_USER."\n";


$slowBootrap = new S2lowBootstrap();
$slowBootrap->bootstrap($sqlQuery);
