<?php 

include("init.php");

$name =  Helpers::getVarFromPost('name');
if(!$name){
	header('Location: admin_services.php');
	exit;
}

$authority_id =  Helpers::getVarFromPost('authority_id');
if ($authority_id){

	$authorities = $me->getAllPossibleAuthority();
	if (! in_array($authority_id,array_keys($authorities))){
		header("Location: admin_services.php");
		exit;
	}
} else {
	$authority_id = $me->get('authority_id');
}

$result = $serviceUser->add($name,$authority_id);

if (! $result){
	$_SESSION['error'] = "Ce groupe existe déjà !";
}
	
header('Location: admin_services.php?authority_id='.$authority_id);
