<?php 

require_once __DIR__."/../vendor/autoload.php";

set_include_path( 	get_include_path() . PATH_SEPARATOR .
					__DIR__. "/../lib/" . PATH_SEPARATOR .
					__DIR__. "/../model/" . PATH_SEPARATOR .
					__DIR__. "/../controller/" . PATH_SEPARATOR .
					__DIR__ . "/../class/" . PATH_SEPARATOR . 
					__DIR__ . "/../class/actes"  . PATH_SEPARATOR . 
					__DIR__ . "/../class/helios"  . PATH_SEPARATOR . 
					__DIR__ . "/../class/mailsec". PATH_SEPARATOR

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


require_once(__DIR__."/../class/util.php");

$sqlQuery = new SQLQuery(DB_DATABASE);
$sqlQuery->setDatabaseHost(DB_HOST);
$sqlQuery->setCredential(DB_USER,DB_PASSWORD);
$sqlQuery->setClientEncoding(DB_CLIENT_ENCODING);

$objectInstancier = new ObjectInstancier();
$objectInstancier->{'SQLQuery'} = $sqlQuery;

if (isset($_SESSION)) {
    $objectInstancier->set("SessionWrapper", new SessionWrapper($_SESSION));
    $environnement = new Environnement($_GET,$_POST,$_REQUEST,$_SESSION,$_SERVER);

} else {
    $session = array();
    $objectInstancier->set("SessionWrapper", new SessionWrapper($session));
    $environnement = new Environnement($_GET,$_POST,$_REQUEST,$session,$_SERVER);

}
$objectInstancier->set("Environnement",$environnement);
$objectInstancier->set("website_ssl",WEBSITE_SSL);
$objectInstancier->set("website",WEBSITE);

$objectInstancier->set('database_json_definition_filepath',__DIR__."/../db/s2low.sql.json");
$objectInstancier->set('database_sql_definition_filepath',__DIR__."/../db/s2low.sql");

$objectInstancier->set("openstack_authentication_url_v2",OPENSTACK_AUTHENTICATION_URL_V2);
$objectInstancier->set("openstack_username",OPENSTACK_USERNAME);
$objectInstancier->set("openstack_password",OPENSTACK_PASSWORD);
$objectInstancier->set("openstack_tenant",OPENSTACK_TENANT);
$objectInstancier->set("openstack_region",OPENSTACK_REGION);
$objectInstancier->set("openstack_swift_container_prefix",OPENSTACK_SWIFT_CONTAINER_PREFIX);

$objectInstancier->set("helios_files_upload_root",HELIOS_FILES_UPLOAD_ROOT);

$objectInstancier->set("actes_files_upload_root",ACTES_FILES_UPLOAD_ROOT);
$objectInstancier->set("actes_appli_trigramme",ACTES_APPLI_TRIGRAMME);


$frontController = new FrontController($objectInstancier);

ObjectInstancierFactory::setObjectInstancier($objectInstancier);