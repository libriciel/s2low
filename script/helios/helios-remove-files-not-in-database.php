<?php

require_once(__DIR__ . "/../../init/init.php");
$objectInstancier = LegacyObjectsManager::getLegacyObjectInstancier()->get(ObjectInstancier::class);

$dh = opendir(HELIOS_FILES_UPLOAD_ROOT);


$heliosTransactionsSQL = $objectInstancier->get(HeliosTransactionsSQL::class);

while (($file = readdir($dh)) !== false) {
    if (in_array($file, array('.','..'))) {
        continue;
    }

    $nb_files = $heliosTransactionsSQL->isDuplicate($file);
    echo "Fichier $file : $nb_files trouvé(s)\n";

    if ($nb_files == 0) {
        echo "Destruction du fichier\n";
        //unlink(HELIOS_FILES_UPLOAD_ROOT."/$file");
    }
}
closedir($dh);
