<?php

require_once( __DIR__."/../../init/init.php");

if ( $argc != 3 ){
	echo "Usage : {$argv[0]} authority_id file.zip\n";
	echo "\tsauvegarde tous les PES_Acquit dans le fichier file.zip\n";
	exit;
}

$authority_id = $argv[1];
$zip_file = $argv[2];


$sql = "SELECT acquit_filename FROM helios_transactions" . " WHERE authority_id=? AND acquit_filename IS NOT NULL";

$aquit_filename_list = $sqlQuery->queryOneCol($sql,$authority_id);

if (count($aquit_filename_list) <= 0){
	echo "Aucun fichier trouvé => sortie\n";
	exit;
}

echo count($aquit_filename_list)." fichiers trouvés\n";

$zip = new ZipArchive;
$res = $zip->open($zip_file, ZipArchive::CREATE);
if ($res !== TRUE) {
	echo "Impossible d'ouvrir le fichier $zip_file\n";
	exit;
}

foreach($aquit_filename_list as $acquit_filename){
	$filename = HELIOS_RESPONSES_ROOT.$acquit_filename;
	$zip->addFile($filename,$acquit_filename);
	echo "$filename\n";
}
$zip->close();
echo "Fichiers sauvegardés dans $zip_file\n";

