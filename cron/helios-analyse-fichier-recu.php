<?php
require_once( __DIR__ . "/../init/init.php");


$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$authoritySQL = new AuthoritySQL($sqlQuery);
$heliosRetourSQL = new HeliosRetourSQL($sqlQuery);

$heliosAnalyseFichierRecu = new HeliosAnalyseFichierRecu($heliosTransactionSQL,$authoritySQL,$heliosRetourSQL,HELIOS_XSD_PATH);

$heliosAnalyseFichierRecu->analyse(HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH, HELIOS_RESPONSES_ROOT);