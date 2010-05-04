<?php 
require_once("include/init.php");

$name = Helpers::getVarFromPost('name');

$groupe = new GroupeMail();

$id = $groupe->getGroupeIdFromName($name,$me->get('authority_id'));
if ($id){
	$_SESSION['error'] = "Ce groupe existe déjà !";
	header("Location: index.php?command=annuaire");
	exit;
}

$groupe->set("authority_id",$me->get('authority_id'));
$groupe->set('name',$name);
$groupe->save(false);

$_SESSION['message_ok'] = "Groupe $name crée avec succès";
header("Location: index.php?command=annuaire");