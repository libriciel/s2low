<?php

define("TESTING_ENVIRONNEMENT", "true");
define("TRACE_FILE_PATH", "/tmp/s2low-phpunit.log");
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
