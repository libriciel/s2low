<?php

define("TESTING_ENVIRONNEMENT", "true");
define("TRACE_FILE_PATH", "/tmp/s2low-phpunit.log");
define('HELIOS_FILES_UPLOAD_ROOT', "vfs://test/helios/");
define("ANTIVIRUS_COMMAND", "ls");

define("TIMESTAMPING_CERT", __DIR__ . "/fixtures/timestamp_certificates/s2low_timestamp_cert.pem");
define("TIMESTAMPING_PRIV_KEY", __DIR__ . "/fixtures/timestamp_certificates/s2low_timestamp_priv_key.pem");
define("TIMESTAMPING_PRIV_KEY_PASS", __DIR__ . "/fixtures/timestamp_certificates/s2low_timestamp_priv_key.pass");

require_once(__DIR__ . "/../../init/init.php");

/* Uniquement pour la mise à jour de la base de test...*/
$sqlQuery = new SQLQuery(DB_DATABASE_TEST);
$sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
$sqlQuery->setDatabaseHost(DB_HOST_TEST);
$sqlQuery->setClientEncoding(DB_CLIENT_ENCODING);

//$objectInstancier = new ObjectInstancier();
/** @var ObjectInstancier $objectInstancier */
$objectInstancier->set('SQLQuery', $sqlQuery);

$postgreSQLControler = $objectInstancier->get('PostgreSQLController');

$postgreSQLControler->alterDatabase(function ($message) {
    echo $message . "\n";
});


/** @deprecated  */
class PHPUnit_Framework_TestCase extends \PHPUnit\Framework\TestCase
{
    public function setExpectedException($e, string $message)
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }
    public function noAssertion()
    {
        $this->assertTrue(true);
    }
}
