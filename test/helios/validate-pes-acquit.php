<?php

require_once (__DIR__."/../../init/init.php");
$file_path = $argv[1];
$schema_pes_path = HELIOS_XSD_PATH;


$xml = simplexml_load_file($file_path);
if (! $xml){
	throw new Exception("Le fichier $file_path n'est pas bien formé (fichier ignoré)");
}
$root_name = mb_strtolower($xml->getName());

if ($root_name == 'pes_retour'){
	$schema_location = $schema_pes_path."/PES_V2/RETOUR/Rev0/PES_Retour.xsd";
} else {
	//$schema_location = $schema_pes_path."/PES_V2/Rev0/PES_V2_Acquit_Autonome.xsd";
	$schema_location = $schema_pes_path."/PES_V2/Rev0/PES_V2_Acquit_Autonome_V2.xsd";

}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->load($file_path);

$errors = libxml_get_errors();
libxml_clear_errors();

if ($errors){
	throw new Exception("Le fichier $file_path n'est pas bien formé (fichier ignoré)");
}
$dom->schemaValidate($schema_location);
$errors = libxml_get_errors();
libxml_clear_errors();

if ($errors){
	print_r($errors);
	throw new Exception("Le fichier $file_path n'est pas valide (fichier ignoré)");
}
