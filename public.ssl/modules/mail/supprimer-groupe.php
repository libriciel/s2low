<?php


require_once("include/init.php");


$groupe_id = Helpers::getVarFromGet('groupe_id');

$groupe = new GroupeMail($groupe_id);

$nb_user = $groupe->getNbUtilisateur();

$name = $groupe->get('name');

if ($nb_user){
	$_SESSION['error'] = "Le groupe $name n'est pas vide !";
	header("Location: index.php?command=annuaire&groupe_id=$groupe_id");
	exit;
}

$groupe->delete();

$_SESSION['message_ok'] = "Le groupe $name a été supprimé";
header("Location: index.php?command=annuaire");