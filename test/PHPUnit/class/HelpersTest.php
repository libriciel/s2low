<?php

use S2lowLegacy\Class\Helpers;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\Helpers\ResponseHelper;

class HelpersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $_POST = array();
        $_GET = array();
        $_REQUEST = array();
        $_SESSION = array();
    }

    /** @deprecated  */
    public function setExpectedException(string $e, string $message)
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }

    public function testGetVarFromPost()
    {
        $_POST = array('foo' => 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost('foo'));
    }

    public function testGetVarFromGet()
    {
        $_GET = array('foo' => 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet('foo'));
    }

    public function testGetVarFromRequest()
    {
        $_POST = array('foo' => 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromRequest('foo', 'POST'));
    }

    public function testGetVarFromRequestGet()
    {
        $_GET = array('foo' => 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromRequest('foo', 'GET'));
    }

    public function testGetVarFromRequestArray()
    {
        $_GET = array('foo' => array('bar','baz'));
        $this->assertEquals(array('bar','baz'), \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromRequest('foo', 'GET'));
    }

    public function testGetVarFromRequestPutInSession()
    {
        $_GET = array('foo' => 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromRequest('foo', 'GET', true));
    }

    public function testStripSlaches()
    {
        $str = "foo\'bar";
        $this->assertEquals($str, \S2lowLegacy\Class\Helpers\RequestHelper::stripSlashes($str));
    }

    public function testGetFromSession()
    {
        \S2lowLegacy\Class\Helpers\SessionHelper::putInSession('foo', 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession('foo'));
    }

    public function testDeleteFromSession()
    {
        \S2lowLegacy\Class\Helpers\SessionHelper::putInSession('foo', 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession('foo', true));
        $this->assertNull(\S2lowLegacy\Class\Helpers\SessionHelper::getFromSession('foo'));
    }

    public function testPurgeSession()
    {
        \S2lowLegacy\Class\Helpers\SessionHelper::putInSession('foo', 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\Helpers\SessionHelper::getFromSession('foo'));
        \S2lowLegacy\Class\Helpers\SessionHelper::purgeTempSession();
        $this->assertNull(\S2lowLegacy\Class\Helpers\SessionHelper::getFromSession('foo'));
    }

    public function testReturnAndExit()
    {
        $_GET['api'] = 1;
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("OK\nfoo\n");
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(0, "foo");
    }

    public function testReturnAndExitApiMessage()
    {
        $_GET['api'] = 1;
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("OK\nbaz\n");
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(0, "foo", null, "baz");
    }

    public function testReturnAndExitNoRedir()
    {
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("foo\n");
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(0, "foo");
    }

    public function testReturnAndExitRedir()
    {
        $this->setExpectedException("Exception", "foo");
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(0, "foo", true);
    }

    public function testReturnAndExitFailed()
    {
        $_GET['api'] = 1;
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("KO\nfoo\n");
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "foo");
    }

    public function testAnsiDateToTimestamp()
    {
        $this->assertEquals("1442224800", \S2lowLegacy\Class\Helpers\DateHelper::ansiDateToTimestamp("2015-09-14"));
    }
    public function testAnsiDateToTimestampAtMidnight()
    {
        $this->assertEquals("1442181600", \S2lowLegacy\Class\Helpers\DateHelper::ansiDateToTimestamp("2015-09-14", true));
    }

    public function testGetFromBdd()
    {
        $this->assertEquals('foo', \S2lowLegacy\Class\Helpers\StringHelper::getFromBDD('foo'));
    }

    public function testEscapeForXML()
    {
        $this->assertEquals('\\\"foo\\\"', \S2lowLegacy\Class\Helpers\StringHelper::escapeForXML('\"foo\"'));
    }

    public function testGetFromXMLElt()
    {
        $this->assertEquals("école", \S2lowLegacy\Class\Helpers\StringHelper::getFromXMLElt("école"));
    }

    public function testTruncateString()
    {
        $this->assertEquals("foo...", \S2lowLegacy\Class\Helpers\StringHelper::truncateString("foobar", 3, true));
    }

    public function testGetPrettyHours()
    {
        $this->assertEquals("07h 22min 42s", \S2lowLegacy\Class\Helpers\DateHelper::getPrettyHours("07:22:42"));
    }

    public function testGetPrettyHoursFailed()
    {
        $this->assertNull(\S2lowLegacy\Class\Helpers\DateHelper::getPrettyHours("foo"));
    }


    public function testGetTimestampFromBDDDate()
    {
        $this->assertEquals("1442208162", \S2lowLegacy\Class\Helpers\DateHelper::getTimestampFromBDDDate("2015-09-14 07:22:42"));
    }

    public function testGetTimestampFromBDDDateFailed()
    {
        $this->assertNull(\S2lowLegacy\Class\Helpers\DateHelper::getTimestampFromBDDDate("foo"));
    }

    public function testGetDateFromBDDDate()
    {
        $this->assertNull(\S2lowLegacy\Class\Helpers\DateHelper::getDateFromBDDDate("foo"));
    }

    public function testGetDateFromBDDDateOK()
    {
        $this->assertEquals("14 septembre 2015 à 07h22min42s", \S2lowLegacy\Class\Helpers\DateHelper::getDateFromBDDDate("2015-09-14 07:22:42", true));
    }

    public function testGetDateFromBDDBeginningOfYear()
    {
        $this->assertEquals("1 janvier 2022 à 00h00min00s", \S2lowLegacy\Class\Helpers\DateHelper::getDateFromBDDDate("2022-01-01 00:00:00", true));
    }

    public function testGetANSIDateFromBDDDate()
    {
        $this->assertEquals("2015-09-14", \S2lowLegacy\Class\Helpers\DateHelper::getANSIDateFromBDDDate("2015-09-14 07:22:42"));
    }

    public function testGetANSIDateFromBDDDateFailed()
    {
        $this->assertNull(\S2lowLegacy\Class\Helpers\DateHelper::getANSIDateFromBDDDate("foo"));
    }

    public function testGetURLWithParam()
    {
        $_SERVER["QUERY_STRING"] = "";
        $_SERVER["PHP_SELF"] = "";
        $this->assertEquals(\S2lowLegacy\Class\Helpers\UrlHelper::getLink("?foo=bar"), \S2lowLegacy\Class\Helpers\UrlHelper::getURLWithParam(array('foo' => 'bar')));
    }

    public function testCreateDirTree()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        $dir_to_create = "foo/bar/baz";
        $this->assertTrue(\S2lowLegacy\Class\Helpers\FileSystemHelper::createDirTree($testStreamUrl . "/" . $dir_to_create, $testStreamUrl));
        $this->assertTrue(file_exists($testStreamUrl . "/" . $dir_to_create));
    }

    public function testCreateDirTreeFailed()
    {
        $dir_to_create = "foo/bar/baz";
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        $this->assertFalse(\S2lowLegacy\Class\Helpers\FileSystemHelper::createDirTree($dir_to_create, $testStreamUrl));
    }

    public function testCreateDirTreeFailedFileExist()
    {
        $dir_to_create = "foo/bar/baz";
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        file_put_contents($testStreamUrl . "/foo", "foo");
        $this->assertFalse(\S2lowLegacy\Class\Helpers\FileSystemHelper::createDirTree($testStreamUrl . "/" . $dir_to_create, $testStreamUrl));
    }

    public function testCreateDirTreeFailedFileExist2()
    {
        $dir_to_create = "foo/bar/baz";
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        mkdir($testStreamUrl . "/foo/bar", 0777, true);
        file_put_contents($testStreamUrl . "/foo/bar/baz", "baz");
        $this->assertFalse(\S2lowLegacy\Class\Helpers\FileSystemHelper::createDirTree($testStreamUrl . "/" . $dir_to_create, $testStreamUrl));
    }

    public function testCreateDirTreeDirExist()
    {
        $dir_to_create = "foo/bar/baz";
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        mkdir($testStreamUrl . "/" . $dir_to_create, 0777, true);
        $this->assertTrue(\S2lowLegacy\Class\Helpers\FileSystemHelper::createDirTree($testStreamUrl . "/" . $dir_to_create, $testStreamUrl));
    }

    public function testDeleteFromFS()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        mkdir($testStreamUrl . "/foo");
        file_put_contents($testStreamUrl . "/bar", "bar");
        $this->assertTrue(\S2lowLegacy\Class\Helpers\FileSystemHelper::deleteFromFS($testStreamUrl . "/foo", $testStreamUrl . "/bar"));
    }

    public function testDeleteFromFSFailed()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        mkdir($testStreamUrl . "/foo/bar", 0777, true);
        $this->assertFalse(\S2lowLegacy\Class\Helpers\FileSystemHelper::deleteFromFS($testStreamUrl . "/foo", $testStreamUrl . "/bar"));
    }

    public function testFixPerms()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        $this->assertFalse(\S2lowLegacy\Class\Helpers\FileSystemHelper::fixPerms($testStreamUrl . "/foo"));
    }

    public function testFixPermsFile()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        file_put_contents($testStreamUrl . "/bar", "bar");
        $this->assertTrue(\S2lowLegacy\Class\Helpers\FileSystemHelper::fixPerms($testStreamUrl . "/bar"));
    }

    public function testGetFileType()
    {
        $this->assertNull(\S2lowLegacy\Class\Helpers\FileSystemHelper::getFileType("foo"));
    }

    public function testGetFileTypeTrueFile()
    {
        $this->assertEquals("text/x-php", \S2lowLegacy\Class\Helpers\FileSystemHelper::getFileType(__FILE__));
    }

    public function testGetTempName()
    {
        $this->assertMatchesRegularExpression("#^__tmp__[0-9]{8}$#", \S2lowLegacy\Class\Helpers\StringHelper::genTempName());
    }

    public function testGetTempNamePrefix()
    {
        $this->assertMatchesRegularExpression("#^[0-9]{8}$#", \S2lowLegacy\Class\Helpers\StringHelper::genTempName(8, false));
    }

    public function testSendFileToBrowserFileNotFound()
    {
        $this->assertFalse(\S2lowLegacy\Class\Helpers\ResponseHelper::sendFileToBrowser("foo", "bar"));
        $this->assertEquals("Fichier spécifié introuvable", ResponseHelper::$last_error);
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testSendFileToBrowser()
    {
        $this->expectOutputRegex("#<?php#");
        $this->assertTrue(\S2lowLegacy\Class\Helpers\ResponseHelper::sendFileToBrowser(__FILE__, basename(__FILE__), \S2lowLegacy\Class\Helpers\FileSystemHelper::getFileType(__FILE__)));
    }

    public function testGetAuthorizedCACertsEmpty()
    {
        $this->assertEmpty(\S2lowLegacy\Class\Helpers\CertificateHelper::getAuthorizedCACerts("foo"));
    }

    public function testGetAuthorizedCACerts()
    {
        $certs = \S2lowLegacy\Class\Helpers\CertificateHelper::getAuthorizedCACerts(__DIR__ . "/fixtures/");
        $this->assertEquals("ADULLACT-Projet", $certs[0]['subject']['O']);
    }

    public function testGetAuthorizedCACertsEmptyDir()
    {
        $this->assertEmpty(\S2lowLegacy\Class\Helpers\CertificateHelper::getAuthorizedCACerts(__DIR__ . "/fixtures/empty/"));
    }

    public function testNullIntFromPost()
    {
        $this->assertEquals(
            null,
            \S2lowLegacy\Class\Helpers\RequestHelper::getIntFromPost("test", true)
        );
    }

    public function testNullExceptionIntFromPost()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("test est null");
        \S2lowLegacy\Class\Helpers\RequestHelper::getIntFromPost("test", false);
    }

    /**
     * @dataProvider checkIntProvider
     * @return void
     */

    public function testCheckInt($varEntree, $varSortie, $nullable)
    {
        $this->assertEquals(
            $varSortie,
            \S2lowLegacy\Class\Helpers\RequestHelper::checkInt($varEntree, $nullable, "test")
        );
    }

    public function checkIntProvider()
    {
        return [
            ["1","1",false],
            ["0","0",false],
            [null,null, true],
            ["","",true]
        ];
    }

    /**
     * @dataProvider checkIntProviderWithError
     * @return void
     */

    public function testCheckIntWithError($var, $nullable)
    {
        $this->expectException(UnexpectedValueException::class);
        \S2lowLegacy\Class\Helpers\RequestHelper::checkInt($var, $nullable, "test");
    }

    public function checkIntProviderWithError()
    {
        return [
            ["fsdfsqfdsqd", false],
            ["fsdfsqfdsqd", true],
            [null, false]
        ];
    }

    /**
     * @dataProvider checkDateProvider
     * @return void
     */

    public function testCheckDate($var, $nullable)
    {
        $this->assertEquals(
            $var,
            \S2lowLegacy\Class\Helpers\RequestHelper::checkDate($var, $nullable, "test")
        );
    }

    public function checkDateProvider(): array
    {
        return [
            ["2022-1-1", true],
            ["2022-1-31", true],
            ["2020-2-29", true],
            ["2020-12-31", true],
            ["2022-1-1", false],
            ["2022-1-31", false],
            ["2020-2-29", false],
            ["2020-12-31", false],
            ["", true],
            [null, true]
        ];
    }


    /**
     * @dataProvider checkDateProviderWithError
     * @return void
     */

    public function testCheckDateWithError($var, $nullable)
    {
        $this->expectException(UnexpectedValueException::class);
        \S2lowLegacy\Class\Helpers\RequestHelper::checkDate($var, $nullable, "test");
    }

    public function checkDateProviderWithError(): array
    {
        return [
            ["2022-1-32",true],
            ["2020-13-31",true],
            ["fdsfsqdfsdq",true],
            ["2022-1-32",false],
            ["2020-13-31",false],
            ["fdsfsqdfsdq",false],
            [null,false],
            ["",false]
        ];
    }
}
