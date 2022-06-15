<?php

define("TESTING_ENVIRONNEMENT","true");
define("TRACE_FILE_PATH","/tmp/s2low-phpunit.log");
define('HELIOS_FILES_UPLOAD_ROOT', "vfs://test/helios/");
define("ANTIVIRUS_COMMAND","ls");

define("TIMESTAMPING_CERT",__DIR__."/fixtures/timestamp_certificates/s2low_timestamp_cert.pem");
define("TIMESTAMPING_PRIV_KEY",__DIR__."/fixtures/timestamp_certificates/s2low_timestamp_priv_key.pem");
define("TIMESTAMPING_PRIV_KEY_PASS",__DIR__."/fixtures/timestamp_certificates/s2low_timestamp_priv_key.pass");

set_include_path(__DIR__."/../../ext/" . PATH_SEPARATOR .   get_include_path());

require_once __DIR__."/../../docker-resources/define-from-environnement.php";


require_once(__DIR__."/../../init/init.php");


require_once(__DIR__."/S2lowTestCase.class.php");
require_once(__DIR__."/S2lowSimpleTestCase.class.php");
require_once(__DIR__."/ActesUtilitiesTestTrait.php");
require_once(__DIR__."/PastellConfigurationTestTrait.php");
require_once(__DIR__."/HeliosUtilitiesTestTrait.php");
require_once(__DIR__."/RgsConnexionTestTrait.php");
require_once(__DIR__."/MailsecUtilitiesTestTrait.php");

/* Uniquement pour la mise à jour de la base de test...*/
$sqlQuery = new SQLQuery(DB_DATABASE_TEST);
$sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
$sqlQuery->setDatabaseHost(DB_HOST_TEST);
$sqlQuery->setClientEncoding(DB_CLIENT_ENCODING);

//$objectInstancier = new ObjectInstancier();
/** @var ObjectInstancier $objectInstancier */
$objectInstancier->set('SQLQuery',$sqlQuery);

$postgreSQLControler = $objectInstancier->get('PostgreSQLController');

$postgreSQLControler->alterDatabase(function($message){echo $message . "\n";});


/** @deprecated  */
class PHPUnit_Framework_TestCase extends \PHPUnit\Framework\TestCase{

	public function setExpectedException( $e,string $message){
		$this->expectException($e);
		$this->expectExceptionMessage($message);
	}
	public function noAssertion(){
		$this->assertTrue(true);
	}

}
