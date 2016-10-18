<?php

$_GET['api'] = 1;

require_once( __DIR__ . "/../init/init.php");


echo "OK";

$authentification = Authentification::getInstance();

$connexion_info = $authentification->getAllConnexionInfo();

print_r($connexion_info);

$userSQL = new UserSQL($sqlQuery);

$id_list = $userSQL->getIdFromConnexionInfo(
	$connexion_info['certificate_hash'],
	$connexion_info['certificate_rgs_2_etoiles'],
	$connexion_info['login'],
	$connexion_info['password']
);

print_r($id_list);