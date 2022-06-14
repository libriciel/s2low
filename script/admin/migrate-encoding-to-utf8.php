<?php

use Symfony\Component\Finder\Finder;

require_once( __DIR__."/../../init/init.php");

function changeEncoding($filepath){
    echo "Fichier traité : $filepath\n";
    $oldContent = file_get_contents($filepath);
    try{
        $newContent = mb_convert_encoding($oldContent,'UTF-8',"ISO-8859-9");
    } catch (Exception $exception){
        echo "Exception";
        die();
    }
    file_put_contents($filepath,$newContent);
}

$basePath = realpath(__DIR__."/../../");

$finder = new Finder();

$finder->files()->in($basePath)->exclude("vendor")->name("*.php");

$aVerifier = [];

foreach ($finder as $file) {
    $absoluteFilePath = $file->getRealPath();
    $fileNameWithExtension = $file->getRelativePathname();
    $mb_detect_encoding = mb_detect_encoding(file_get_contents($absoluteFilePath));
    if($mb_detect_encoding != "UTF-8"){
        echo "$mb_detect_encoding traité\n";
        if($mb_detect_encoding != "ASCII"){
            $aVerifier[] = $file->getRelativePathname();
        }
        changeEncoding($absoluteFilePath);
    } else{
        echo "$mb_detect_encoding non traité\n";
    }
}

var_dump($aVerifier);