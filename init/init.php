<?php 

set_include_path( 	get_include_path() . PATH_SEPARATOR .
					__DIR__. "/../lib/" . PATH_SEPARATOR .
					__DIR__. "/../core/" . PATH_SEPARATOR .
					__DIR__. "/../model/" . PATH_SEPARATOR .
					__DIR__. "/../controller/" . PATH_SEPARATOR .
					__DIR__ . "/../class/" . PATH_SEPARATOR . 
					__DIR__ . "/../class/actes"  . PATH_SEPARATOR . 
					__DIR__ . "/../class/helios"  . PATH_SEPARATOR . 
					__DIR__ . "/../class/mailsec". PATH_SEPARATOR . 
					__DIR__ . "/../class/dia"
					);
					
if ( ! function_exists('s2low_autoload')) {
	function s2low_autoload($class_name) {
		@ $result = include($class_name . '.class.php');
		if ( ! $result ){
			return false;
		}
		return true;
	}
}

spl_autoload_register('s2low_autoload');


require_once(__DIR__."/../config/config.php");

//A cause du chargement d'objet à partir de la session ... BEURK !
require_once(SITEROOT."/public.ssl/modules/mail/lib/Annuaire.class.php");

require_once(SITEROOT . '/class/include.class.php');

require_once("util.php");

$sqlQuery = new SQLQuery(DB_DATABASE);
$sqlQuery->setDatabaseHost(DB_HOST);
$sqlQuery->setCredential(DB_USER,DB_PASSWORD);
$sqlQuery->setClientEncoding(DB_CLIENT_ENCODING);

function sortir($message) {
	$_SESSION["error"] = $message;
	header("Location: " . WEBSITE_SSL);
	exit;
};

$objectInstancier = new ObjectInstancier();
$objectInstancier->SQLQuery = $sqlQuery;

$frontController = new FrontController($objectInstancier);

