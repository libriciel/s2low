<?php 

include("init.php");

$id =  Helpers::getVarFromPost('id');
if(!$id){
	header('Location: admin_services.php');
	exit;
}

$serviceUser->removeParent($id);

$service_id = Helpers::getVarFromPost('service_id');
$groupe = $serviceUser->getInfo($id);

$parent = $serviceUser->getPossibleParent($groupe['authority_id'],$id);

$allParent = array();
foreach($parent as $service){
	$allParent[] = $service['id'];
}

if (in_array($service_id,$allParent)){
	$serviceUser->addParent($id,$service_id);
}

header('Location: gestion-service-content.php?id='.$id);
