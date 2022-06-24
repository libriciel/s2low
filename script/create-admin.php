<?php

require_once(__DIR__ . "/../init/init.php");

if ($argc < 5) {
    echo "{$argv[0]} : Crée un utilisateur avec le rôle SUPER ADMIN\n";
    echo "Usage : {$argv[0]} nom prenom email fichier_certificat_pem\n";
    exit(-1);
}

$name = $argv[1];
$givenname = $argv[2];
$email = $argv[3];
$certificate = $argv[4];

$him = new User();

$him->set("name", $name);
$him->set("givenname", $givenname);
$him->set("email", $email);
$him->set("status", 1);
$him->set("authority_id", 1);
$him->set("role", 'SADM');

$him->set("certFilePath", $certificate);
if (! $him->save()) {
    echo "Erreur lors de l'enregistrement de l'utilisateur : " . $him->getErrorMsg() . " \n";
    exit(-1);
}

$user_id = $him->getId();

$userSQL = new UserSQL($sqlQuery);
$userSQL->saveCertificateRGS2Etoiles($user_id, "");

echo "Utilisateur créé avec succès";
