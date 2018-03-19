<?php

require_once __DIR__."/../../init/init.php";

if ($argc < 1){
    echo "Usage {$argv[0]} archive_path\n";
    echo "{$argv[0]} : permet d'afficher la validation d'une enveloppe tel que s2low le ferait";
    exit(-1);
}

$archive_path = $argv[1];

$archive = new \Libriciel\LibActes\ArchiveValidator($objectInstancier->get('actes_appli_trigramme'));

try {
	$archive->validate($archive_path);
} catch (\Libriciel\LibActes\Utils\XSDValidationException $e){
	print_r($e->displayValidationErrors());
} catch (Exception $e){
    echo "L'archive n'est pas valide : " . $e->getMessage();
    return false;
}

echo "L'archive est valide";


