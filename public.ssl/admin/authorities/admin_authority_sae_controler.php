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

$_SESSION["error"] = "Les informations ont été mises à jour";
header("Location: admin_authority_sae.php?id=$id");