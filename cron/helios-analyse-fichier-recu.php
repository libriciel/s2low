<?php
require_once( __DIR__ . "/../init/init.php");

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;



$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$authoritySQL = new AuthoritySQL($sqlQuery);
$heliosRetourSQL = new HeliosRetourSQL($sqlQuery);
$authoritySiretSQL = new AuthoritySiretSQL($sqlQuery);

$heliosAnalyseFichierRecu = new HeliosAnalyseFichierRecu($heliosTransactionSQL,$authoritySQL,$heliosRetourSQL,$authoritySiretSQL, HELIOS_XSD_PATH,EMAIL_ADMIN,TDT_FROM_EMAIL);

$heliosAnalyseFichierRecu->analyse(HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH, HELIOS_RESPONSES_ROOT,HELIOS_RESPONSES_ERROR_PATH,HELIOS_OCRE_FILE_PATH);

$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	echo "Arret du script : $sleep \n";
	sleep($sleep);
}
