<?php 

include("init.php");

$id_service =  Helpers::getVarFromPost('id');

$serviceUser->supprimerService($id_service);
	
header('Location: admin_services.php');
