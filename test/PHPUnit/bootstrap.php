<?php

define("TESTING_ENVIRONNEMENT", "true");
define("TRACE_FILE_PATH", "/tmp/s2low-phpunit.log");
define('HELIOS_FILES_UPLOAD_ROOT', "vfs://test/helios/");
define("ANTIVIRUS_COMMAND", "ls");

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

