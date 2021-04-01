<?php

require_once( __DIR__."/../../init/init.php");

// Groupe id
$groupe = 1;
// nom de domaine origine
$domaineOrigine = "ecollectivite.fr";
// nom de domaine cible
$domaineCible = "ecollectivite2.fr";

$userClass = $objectInstancier->get(User::class);

$users= $userClass->getUsersList("WHERE authorities.authority_group_id= $groupe");

foreach ($users as $user){
    $new = preg_replace("#@$domaineOrigine#","@$domaineCible",$user["email"]);
    echo "{$user["id"]};{$user["email"]}\n";
    if($new != $user["email"]){
        $sql = "UPDATE users SET email=? WHERE id=?";
        echo "{$user["id"]};{$user["email"]};$new\n";
        $sqlQuery->query($sql, $new, $user["id"]);
    }
}

