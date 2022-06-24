<?php

require_once(__DIR__ . "/../../init/init.php");

$heliosTransactionsSQL = $objectInstancier->get('HeliosTransactionsSQL');


$dh = opendir(HELIOS_FILES_UPLOAD_ROOT);
while (($file = readdir($dh)) !== false) {
    if (in_array($file, array('.','..'))) {
        continue;
    }
    if ($heliosTransactionsSQL->isDuplicate($file)) {
        continue;
    }
    echo $file . "\n";
}
