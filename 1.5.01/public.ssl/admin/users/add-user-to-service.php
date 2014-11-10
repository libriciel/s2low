<?php 

include(dirname(__FILE__)."/../services/init.php");

$id_service =  Helpers::getVarFromPost('id_service');
$id_user =  Helpers::getVarFromPost('id_user');

$result = $serviceUser->addUser($id_user,$id_service);

header('Location: admin_user_edit.php?id='.$id_user);
