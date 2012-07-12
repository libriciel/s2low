<?php 
require_once( __DIR__ . "/../../../init/init-www-actes.php");

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

$authorityInfo = $authoritySQL->getInfo($id);

if (! $authorityInfo  || ! $droit->hasDroit($userInfo,$authorityInfo)){
	sortir("Accès refusé");
}

foreach(AuthoritySQL::getSAEProperties() as $name => $label){
	$info[$name] = $recuperateur->get($name,''); 
}
$authoritySQL->updateSAE($id,$info);

$sedaTest = new SEDATest();
$actesArchiveSEDA = new ActesArchiveSEDA("/tmp");

$authorityInfo = $authoritySQL->getInfo($id);
$actesArchiveSEDA->setAuthorityInfo($authorityInfo);
$bordereau = $actesArchiveSEDA->getBordereau($sedaTest->getTransactionTest());


if ( ! $sedaTest->validateBordereau($bordereau)){
	$_SESSION["error"]  = "Erreur sur le fichier XML : <br/>" . $sedaTest->getLastError();
} else {
	$_SESSION["error"] = "Les informations ont été mises à jour";
}
header("Location: admin_authority_sae.php?id=$id");