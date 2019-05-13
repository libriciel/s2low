<?php

require_once( __DIR__ . "/../../../../init/init-www-helios.php");

if ($userInfo['role'] != 'SADM'){
	$_SESSION["error"] = "Super admin only !";
	header("Location: " . WEBSITE);
	exit();
}

$recuperateur = new Recuperateur($_GET);
$filename = $recuperateur->get('file');

$heliosResponsesError = new HeliosResponsesError();
$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$authoritySQL = new AuthoritySQL($sqlQuery);
$heliosRetourSQL = new HeliosRetourSQL($sqlQuery);
$authoritySiretSQL = new AuthoritySiretSQL($sqlQuery);

$heliosAnalyseFichierRecu = $objectInstancier->get(HeliosAnalyseFichierRecu::class);


$_SESSION['error'] = "";

ob_start();
try {
	$filepath = $heliosResponsesError->getFilepath($filename);

	$heliosAnalyseFichierRecu->analyseOneFile($filepath,HELIOS_RESPONSES_ROOT,HELIOS_OCRE_FILE_PATH,true);

} catch (Exception $e){


	$_SESSION['error'] = $e->getMessage();
}

$message = ob_get_contents();
ob_end_clean();
$_SESSION['error'] .= "<br/>".nl2br($message);

header("Location: responses-helios-error.php");


