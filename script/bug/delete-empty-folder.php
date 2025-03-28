<?php

use S2lowLegacy\Class\LegacyObjectsManager;
use Symfony\Component\Finder\Finder;

require_once __DIR__ . "/../../init/init.php";
LegacyObjectsManager::setLegacyObjectInstancier();
$actes_files_upload_root = LegacyObjectsManager::getLegacyObjectInstancier()->get('actes_files_upload_root');


$i = 0;

while (true) {
    $finder = new Finder();
    $finder
        ->in($actes_files_upload_root)
        ->directories()
        ->filter(
            function (\SplFileInfo $dirname) {
                return count(scandir($dirname)) == 2;
            }
        );
    if (!$finder->hasResults()) {
        exit;
    }
    $all = [];
    $nb_file = 0;

    /*
     * On ne peux pas parcourir l'itérateur et supprimer un repertoire en même temps...
     * Du coup, on prends les 100 premiers truc et on recommence
     */

    foreach ($finder as $directory) {
        $all[] = $directory->getRealPath();
        if (++$nb_file >= 100) {
            break;
        }
    }
    foreach ($all as $rep) {
        echo ++$i . ". remove $rep\n";
        rmdir($rep);
    }
}
