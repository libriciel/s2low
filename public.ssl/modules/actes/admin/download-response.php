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

try {
    $actesResponsesError->download($filename);
} catch (Exception $e){
	$_SESSION['error'] = $e->getMessage();
	header("Location: responses-actes-error.php");
}