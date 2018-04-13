<?php

require_once( __DIR__ . "/../../../../init/init-www-actes.php");

if ($userInfo['role'] != 'SADM'){
	$_SESSION["error"] = "Super admin only !";
	header("Location: " . WEBSITE);
	exit();
}

$recuperateur = new Recuperateur($_GET);
$filename = $recuperateur->get('file');

$actesResponsesError = $objectInstancier->get('ActesResponsesError');

$actesAnalyseFichierRecuController = $objectInstancier->get(ActesAnalyseFichierRecuController::class);


$_SESSION['error'] = "";

ob_start();
try {
	$filepath = $actesResponsesError->getFilepath($filename);

    $actesAnalyseFichierRecuController->analyseOneFile($filepath);
	$tmpDir = new TmpFolder();
	$tmpDir->delete($filepath);
} catch (Exception $e){
	$_SESSION['error'] = $e->getMessage();
}

$message = ob_get_contents();
ob_end_clean();

if (!$message){
    $message = "Le fichier a été analysé";
}

$_SESSION['error'] .= "<br/>".nl2br($message);

header("Location: responses-actes-error.php");


