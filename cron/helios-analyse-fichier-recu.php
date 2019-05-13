<?php

declare(ticks = 1);

require_once( __DIR__ . "/../init/init.php");

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->setName("helios-analyse-fichier-recu");
$s2lowLogger->enableStdOut(true);

$start = time();
$s2lowLogger->info("Debut ".date("Y-m-d H:i:s",$start));
$min_exec_time = 10;


$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$authoritySQL = new AuthoritySQL($sqlQuery);
$heliosRetourSQL = new HeliosRetourSQL($sqlQuery);
$authoritySiretSQL = new AuthoritySiretSQL($sqlQuery);

$heliosAnalyseFichierRecu = $objectInstancier->get(HeliosAnalyseFichierRecu::class);

$heliosAnalyseFichierRecu->analyse(HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH, HELIOS_RESPONSES_ROOT,HELIOS_RESPONSES_ERROR_PATH,HELIOS_OCRE_FILE_PATH);

$stop = time();
$s2lowLogger->info("Fin ".date("Y-m-d H:i:s",$stop));
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
	$s2lowLogger->info("Arret du script : $sleep");
	sleep($sleep);
}
