<?php

require_once( __DIR__."/../../init/init.php");

$filename = $argv[1];

if (! $filename){
	echo "Usage : {$argv[0]} fichier_a_analyser\n";
	echo "Analyse un fichier recu depuis le FTP helios\nNe procéde pas au déplacement du fichier en cas d'erreu\n";
	exit;
}



$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$authoritySQL = new AuthoritySQL($sqlQuery);
$heliosRetourSQL = new HeliosRetourSQL($sqlQuery);
$authoritySiretSQL = new AuthoritySiretSQL($sqlQuery);

$heliosAnalyseFichierRecu = new HeliosAnalyseFichierRecu($heliosTransactionSQL,$authoritySQL,$heliosRetourSQL,$authoritySiretSQL, HELIOS_XSD_PATH,EMAIL_ADMIN);

$heliosAnalyseFichierRecu->analyseOneFile($filename,HELIOS_RESPONSES_ROOT,HELIOS_OCRE_FILE_PATH,true);

echo "Analyse terminé";

