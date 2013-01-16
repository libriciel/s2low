<?php 

set_include_path( 	get_include_path() . PATH_SEPARATOR .
					__DIR__ . "/../class/" . PATH_SEPARATOR . 
					__DIR__ . "/../class/actes"  . PATH_SEPARATOR . 
					__DIR__ . "/../class/mailsec". PATH_SEPARATOR . 
					__DIR__ . "/../class/dia"
					);
					

function __autoload($class_name) {
    require_once($class_name . '.class.php');
}


require_once(dirname(__FILE__)."/../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

require_once("util.php");

$sqlQuery = new SQLQuery(DB_DATABASE);
$sqlQuery->setDatabaseHost(DB_HOST);
$sqlQuery->setCredential(DB_USER,DB_PASSWORD);

function sortir($message) {
	$_SESSION["error"] = $message;
	header("Location: " . WEBSITE_SSL);
	exit;
};


