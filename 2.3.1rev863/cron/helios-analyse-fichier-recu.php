<?php
require_once( __DIR__ . "/../init/init.php");


$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$authoritySQL = new AuthoritySQL($sqlQuery);
$heliosRetourSQL = new HeliosRetourSQL($sqlQuery);
$authoritySiretSQL = new AuthoritySiretSQL($sqlQuery);

$heliosAnalyseFichierRecu = new HeliosAnalyseFichierRecu($heliosTransactionSQL,$authoritySQL,$heliosRetourSQL,$authoritySiretSQL, HELIOS_XSD_PATH,EMAIL_ADMIN);

$heliosAnalyseFichierRecu->analyse(HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH, HELIOS_RESPONSES_ROOT,HELIOS_RESPONSES_ERROR_PATH);