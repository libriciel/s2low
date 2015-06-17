<?php 

require_once( __DIR__ . "/../../../init/init-www-actes.php");

$recuperateur = new Recuperateur($_GET);
$id = $recuperateur->getInt('id');

$authorityInfo = $authoritySQL->getInfo($id);

if (! $authorityInfo  || ! $droit->hasDroit($userInfo,$authorityInfo)){
	sortir("Accès refusé");
}		

$asalae = new Asalae($authorityInfo);

$result = $asalae->generateSEDA("Ceci est un test");

if (! $result){
	$_SESSION['error'] ="Le test a échoué : " . $asalae->getLastError();
}

$_SESSION['error'] = "La connexion est ok";
header("Location: admin_authority_sae.php?id=$id");