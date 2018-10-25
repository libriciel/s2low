<?php


require_once( __DIR__ . "/../../../../init/init-www-actes.php");

if ($userInfo['role'] != 'SADM'){
$_SESSION["error"] = "Super admin only !";
header("Location: " . WEBSITE);
exit();
}

$recuperateur = new Recuperateur($_GET);
$authority_id = $recuperateur->getInt('authority_id');
$force = $recuperateur->getInt('force');


$authority = new Authority($authority_id);
$authority->init();

$classificationCreation = new ActesClassificationCreation();
$classificationCreation->unsetFrequencyRestriction();


$result = $classificationCreation->createEnveloppe($authority,null,$force);
Helpers::returnAndExit(
    ! $result,
    $classificationCreation->getLastMessage(),
    WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=$authority_id"
);
