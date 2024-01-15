<?php

use org\bovigo\vfs\vfsStream;

define("TESTING_ENVIRONNEMENT", "true");
define("TRACE_FILE_PATH", "/tmp/s2low-phpunit.log");
define('HELIOS_FILES_ROOT', "vfs://test/helios/");  //Pour tous les tests impliquant le système de fichiers helios,
                                                    // on utilise vfs pour éviter d'écrire sur le
                                                    // disque
vfsStream::setup('test');                // Initialisation du système de fichiers VFS
                                                    // ATTENTION : les tests unitaires ne marcheront pas si les variables
                                                    // d'environnement de l'arborescence helios sont surchargées ...
mkdir(HELIOS_FILES_ROOT);
define("ANTIVIRUS_COMMAND", "ls");

define("TIMESTAMPING_CERT", __DIR__ . "/fixtures/timestamp_certificates/s2low_timestamp_cert.pem");
define("TIMESTAMPING_PRIV_KEY", __DIR__ . "/fixtures/timestamp_certificates/s2low_timestamp_priv_key.pem");
define("TIMESTAMPING_PRIV_KEY_PASS", __DIR__ . "/fixtures/timestamp_certificates/s2low_timestamp_priv_key.pass");

require_once(__DIR__ . "/../../init/init.php");

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
