<?php
// Configuration
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$login = Helpers::getVarFromPost("login");
$password = Helpers::getVarFromPost("password");

unset($_SESSION['error']);

$me = new User();

if (! $me->login($login,md5($password))) {
	$_SESSION["error"] = "Échec de l'authentification";
	header("Location: " . WEBSITE_SSL);
	exit;
}

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  
}


header("Location: " . WEBSITE_SSL);