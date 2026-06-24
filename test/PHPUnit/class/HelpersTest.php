<?php

use S2lowLegacy\Class\Helpers;
use PHPUnit\Framework\TestCase;

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
        $this->assertEquals('bar', Helpers::getVarFromPost('foo'));
    }

    public function testGetVarFromGet()
    {
        $_GET = array('foo' => 'bar');
        $this->assertEquals('bar', Helpers::getVarFromGet('foo'));
    }

    public function testGetVarFromRequest()
    {
        $_POST = array('foo' => 'bar');
        $this->assertEquals('bar', Helpers::getVarFromRequest('foo', 'POST'));
    }

    public function testGetVarFromRequestGet()
    {
        $_GET = array('foo' => 'bar');
        $this->assertEquals('bar', Helpers::getVarFromRequest('foo', 'GET'));
    }

    public function testGetVarFromRequestArray()
    {
        $_GET = array('foo' => array('bar','baz'));
        $this->assertEquals(array('bar','baz'), Helpers::getVarFromRequest('foo', 'GET'));
    }

    public function testGetVarFromRequestPutInSession()
    {
        $_GET = array('foo' => 'bar');
        $this->assertEquals('bar', Helpers::getVarFromRequest('foo', 'GET', true));
    }

    public function testStripSlaches()
    {
        $str = "foo\'bar";
        $this->assertEquals($str, Helpers::stripSlashes($str));
    }

    public function testGetFromSession()
    {
        Helpers::putInSession('foo', 'bar');
        $this->assertEquals('bar', Helpers::getFromSession('foo'));
    }

    public function testDeleteFromSession()
    {
        Helpers::putInSession('foo', 'bar');
        $this->assertEquals('bar', Helpers::getFromSession('foo', true));
        $this->assertNull(Helpers::getFromSession('foo'));
    }

    public function testPurgeSession()
    {
        Helpers::putInSession('foo', 'bar');
        $this->assertEquals('bar', Helpers::getFromSession('foo'));
        Helpers::purgeTempSession();
        $this->assertNull(Helpers::getFromSession('foo'));
    }

    public function testReturnAndExit()
    {
        $_GET['api'] = 1;
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("OK\nfoo\n");
        Helpers::returnAndExit(0, "foo");
    }

    public function testReturnAndExitApiMessage()
    {
        $_GET['api'] = 1;
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("OK\nbaz\n");
        Helpers::returnAndExit(0, "foo", null, "baz");
    }

    public function testReturnAndExitNoRedir()
    {
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("foo\n");
        Helpers::returnAndExit(0, "foo");
    }

    public function testReturnAndExitRedir()
    {
        $this->setExpectedException("Exception", "foo");
        Helpers::returnAndExit(0, "foo", true);
    }

    public function testReturnAndExitFailed()
    {
        $_GET['api'] = 1;
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("KO\nfoo\n");
        Helpers::returnAndExit(1, "foo");
    }

    public function testAnsiDateToTimestamp()
    {
        $this->assertEquals("1442224800", Helpers::ansiDateToTimestamp("2015-09-14"));
    }
    public function testAnsiDateToTimestampAtMidnight()
    {
        $this->assertEquals("1442181600", Helpers::ansiDateToTimestamp("2015-09-14", true));
    }

    public function testGetFromBdd()
    {
        $this->assertEquals('foo', Helpers::getFromBDD('foo'));
    }

    public function testEscapeForXML()
    {
        $this->assertEquals('\\\"foo\\\"', Helpers::escapeForXML('\"foo\"'));
    }

    public function testGetFromXMLElt()
    {
        $this->assertEquals("école", Helpers::getFromXMLElt("école"));
    }

    public function testTruncateString()
    {
        $this->assertEquals("foo...", Helpers::truncateString("foobar", 3, true));
    }

    public function testGetPrettyHours()
    {
        $this->assertEquals("07h 22min 42s", Helpers::getPrettyHours("07:22:42"));
    }

    public function testGetPrettyHoursFailed()
    {
        $this->assertNull(Helpers::getPrettyHours("foo"));
    }


    public function testGetTimestampFromBDDDate()
    {
        $this->assertEquals("1442208162", Helpers::getTimestampFromBDDDate("2015-09-14 07:22:42"));
    }

    public function testGetTimestampFromBDDDateFailed()
    {
        $this->assertNull(Helpers::getTimestampFromBDDDate("foo"));
    }

    public function testGetDateFromBDDDate()
    {
        $this->assertNull(Helpers::getDateFromBDDDate("foo"));
    }

    public function testGetDateFromBDDDateOK()
    {
        $this->assertEquals("14 septembre 2015 à 07h22min42s", Helpers::getDateFromBDDDate("2015-09-14 07:22:42", true));
    }

    public function testGetDateFromBDDBeginningOfYear()
    {
        $this->assertEquals("1 janvier 2022 à 00h00min00s", Helpers::getDateFromBDDDate("2022-01-01 00:00:00", true));
    }

    public function testGetANSIDateFromBDDDate()
    {
        $this->assertEquals("2015-09-14", Helpers::getANSIDateFromBDDDate("2015-09-14 07:22:42"));
    }

    public function testGetANSIDateFromBDDDateFailed()
    {
        $this->assertNull(Helpers::getANSIDateFromBDDDate("foo"));
    }

    public function testGetURLWithParam()
    {
        $_SERVER["QUERY_STRING"] = "";
        $_SERVER["PHP_SELF"] = "";
        $this->assertEquals(Helpers::getLink("?foo=bar"), Helpers::getURLWithParam(array('foo' => 'bar')));
    }

    public function testCreateDirTree()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        $dir_to_create = "foo/bar/baz";
        $this->assertTrue(Helpers::createDirTree($testStreamUrl . "/" . $dir_to_create, $testStreamUrl));
        $this->assertTrue(file_exists($testStreamUrl . "/" . $dir_to_create));
    }

    public function testCreateDirTreeFailed()
    {
        $dir_to_create = "foo/bar/baz";
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        $this->assertFalse(Helpers::createDirTree($dir_to_create, $testStreamUrl));
    }

    public function testCreateDirTreeFailedFileExist()
    {
        $dir_to_create = "foo/bar/baz";
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        file_put_contents($testStreamUrl . "/foo", "foo");
        $this->assertFalse(Helpers::createDirTree($testStreamUrl . "/" . $dir_to_create, $testStreamUrl));
    }

    public function testCreateDirTreeFailedFileExist2()
    {
        $dir_to_create = "foo/bar/baz";
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        mkdir($testStreamUrl . "/foo/bar", 0777, true);
        file_put_contents($testStreamUrl . "/foo/bar/baz", "baz");
        $this->assertFalse(Helpers::createDirTree($testStreamUrl . "/" . $dir_to_create, $testStreamUrl));
    }

    public function testCreateDirTreeDirExist()
    {
        $dir_to_create = "foo/bar/baz";
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        mkdir($testStreamUrl . "/" . $dir_to_create, 0777, true);
        $this->assertTrue(Helpers::createDirTree($testStreamUrl . "/" . $dir_to_create, $testStreamUrl));
    }

    public function testDeleteFromFS()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        mkdir($testStreamUrl . "/foo");
        file_put_contents($testStreamUrl . "/bar", "bar");
        $this->assertTrue(Helpers::deleteFromFS($testStreamUrl . "/foo", $testStreamUrl . "/bar"));
    }

    public function testDeleteFromFSFailed()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        mkdir($testStreamUrl . "/foo/bar", 0777, true);
        $this->assertFalse(Helpers::deleteFromFS($testStreamUrl . "/foo", $testStreamUrl . "/bar"));
    }

    public function testFixPerms()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        $this->assertFalse(Helpers::fixPerms($testStreamUrl . "/foo"));
    }

    public function testFixPermsFile()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        file_put_contents($testStreamUrl . "/bar", "bar");
        $this->assertTrue(Helpers::fixPerms($testStreamUrl . "/bar"));
    }

    public function testGetFileType()
    {
        $this->assertNull(Helpers::getFileType("foo"));
    }

    public function testGetFileTypeTrueFile()
    {
        $this->assertEquals("text/x-php", Helpers::getFileType(__FILE__));
    }

    public function testGetTempName()
    {
        $this->assertMatchesRegularExpression("#^__tmp__[0-9]{8}$#", Helpers::genTempName());
    }

    public function testGetTempNamePrefix()
    {
        $this->assertMatchesRegularExpression("#^[0-9]{8}$#", Helpers::genTempName(8, false));
    }

    public function testSendFileToBrowserFileNotFound()
    {
        $this->assertFalse(Helpers::sendFileToBrowser("foo", "bar"));
        $this->assertEquals("Fichier spécifié introuvable", Helpers::$last_error);
    }

    public function testSendFileToBrowser()
    {
        $this->expectOutputRegex("#<?php#");
        $this->assertTrue(Helpers::sendFileToBrowser(__FILE__, basename(__FILE__), Helpers::getFileType(__FILE__)));
    }

    public function testGetAuthorizedCACertsEmpty()
    {
        $this->assertEmpty(Helpers::getAuthorizedCACerts("foo"));
    }

    public function testGetAuthorizedCACerts()
    {
        $certs = Helpers::getAuthorizedCACerts(__DIR__ . "/fixtures/");
        $this->assertEquals("ADULLACT-Projet", $certs[0]['subject']['O']);
    }

    public function testGetAuthorizedCACertsEmptyDir()
    {
        $this->assertEmpty(Helpers::getAuthorizedCACerts(__DIR__ . "/fixtures/empty/"));
    }

    public function testNullIntFromPost()
    {
        $this->assertEquals(
            null,
            Helpers::getIntFromPost("test", true)
        );
    }

    public function testNullExceptionIntFromPost()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("test est null");
        Helpers::getIntFromPost("test", false);
    }

    /**
     * @dataProvider checkIntProvider
     * @return void
     */

    public function testCheckInt($varEntree, $varSortie, $nullable)
    {
        $this->assertEquals(
            $varSortie,
            Helpers::checkInt($varEntree, $nullable, "test")
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
        Helpers::checkInt($var, $nullable, "test");
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
            Helpers::checkDate($var, $nullable, "test")
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
        Helpers::checkDate($var, $nullable, "test");
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
