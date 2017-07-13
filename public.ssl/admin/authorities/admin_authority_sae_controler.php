<?php 
require_once( __DIR__ . "/../../../init/init-www-actes.php");

$recuperateur = new Recuperateur($_POST);
$id = $recuperateur->getInt('id');

$authorityInfo = $authoritySQL->getInfo($id);

if (! $authorityInfo  || ! $droit->isSuperAdmin($userInfo)){
    $objectInstancier->get('S2lowRedirect')->redirect("/","Accès refusé");
}

foreach(AuthoritySQL::getSAEProperties() as $name => $label){
	$info[$name] = $recuperateur->get($name,''); 
}
$info['pastell_id_e'] = $recuperateur->getInt('pastell_id_e',0);


$authoritySQL->updateSAE($id,$info);

$_SESSION["error"] = "Les informations ont été mises à jour";

header("Location: admin_authority_sae.php?id=$id");