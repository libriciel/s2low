<?php 
require_once( __DIR__ . "/../../../init/init-www-actes.php");

$recuperateur = new Recuperateur($_GET);
$id = $recuperateur->getInt('id');

$authorityInfo = $authoritySQL->getInfo($id);

if (! $authorityInfo  || ! $droit->hasDroit($userInfo,$authorityInfo)){
	sortir("Accès refusé");
}

$pastell = new Pastell($authorityInfo['pastell_url'],
						$authorityInfo['pastell_id_e'],
						$authorityInfo['pastell_login'],
						$authorityInfo['pastell_password']);

$result = $pastell->testConnexion();
if ($result){
	$message =  "Connexion OK";
} else {
	$message = $pastell->getLastError();
}

$_SESSION["error"] = $message;

header("Location: admin_authority_sae.php?id=$id");