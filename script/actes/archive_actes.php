<?php 
/*
 * Ce script affiche toutes les informations trouvé dans une
 * archive au format ACTES.
 * 
 * Si une erreur est détécté, elle est affiché à la fin des 
 * informations
 * 
 * 
 * Usage : archive_actes.php archive_actes.tar.gz
 * 
 * Code retour :
 * 				- 0 : l'enveloppe est conforme
 * 				- -1 : erreur de paramètre
 * 				- -2 : l'enveloppe n'est pas conforme
 *
 */

require_once( __DIR__."/../../init/init.php");

define("TMP_PATH","/tmp/");

function sortir($message,$code_erreur = -1){
	echo $message.PHP_EOL;
	exit($code_erreur);
}

if ($argc != 2){
	sortir("Usage:\n {$argv[0]} archive_actes.tar.gz\nou\n{$argv[0]} repository");
}

$archive_path = $argv[1];

echo "Analyze de $archive_path\n";

$envelopeFile = new EnvelopeFile(__DIR__."/../../xsd/actesv1_1.xsd");

$infoEnvelope = $envelopeFile->getInfo($archive_path);
if (! $infoEnvelope ){
	sortir("L'archive n'est pas conforme : " . $envelopeFile->getLastError(),-2);
}

print_r($infoEnvelope);


sortir("L'archive est conforme",0);




