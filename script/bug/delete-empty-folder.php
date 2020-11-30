<?php

use Symfony\Component\Finder\Finder;

require_once __DIR__."/../init/init.php";

$is_empty = function (\SplFileInfo $dirname)  {
    return count(scandir($dirname)) == 2;
};

$finder = new Finder();
$finder
    ->in(ACTES_FILES_UPLOAD_ROOT)
    ->directories()
    ->filter(
        function (\SplFileInfo $dirname)  {
            return count(scandir($dirname)) == 2;
        }
    )
;

foreach(iterator_to_array($finder) as $directory){
    echo "remove $directory\n";
    //rmdir($directory->getRealPath());
}