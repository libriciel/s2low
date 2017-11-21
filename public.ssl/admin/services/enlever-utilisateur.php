<?php 

include("init.php");

$id_service =  Helpers::getVarFromPost('id_service');
$id_users =  Helpers::getVarFromPost('id_user');

if (! $id_users){
    $_SESSION['error']='Il faut sélectionner un utilisateur à enlever du service';
} else {
    foreach($id_users as $id_user){
        $serviceUser->enleverUser($id_service,$id_user);
    }
}


header('Location: gestion-service-content.php?id='.$id_service);
