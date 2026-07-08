<?php

use S2lowLegacy\Class\Helpers;

class HelpersTest extends S2lowTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $_POST = array();
        $_GET = array();
        $_REQUEST = array();
        $_SESSION = array();
    }

    public function testStripSlaches()
    {
        $str = "foo\'bar";
        $this->assertEquals($str, \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->stripSlashes($str));
    }

    public function testGetFromSession()
    {
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->putInSession('foo', 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->getFromSession('foo'));
    }

    public function testDeleteFromSession()
    {
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->putInSession('foo', 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->getFromSession('foo', true));
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->getFromSession('foo'));
    }

    public function testPurgeSession()
    {
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->putInSession('foo', 'bar');
        $this->assertEquals('bar', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->getFromSession('foo'));
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->purgeTempSession();
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->getFromSession('foo'));
    }


    public function testAnsiDateToTimestamp()
    {
        $this->assertEquals("1442224800", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->ansiDateToTimestamp("2015-09-14"));
    }
    public function testAnsiDateToTimestampAtMidnight()
    {
        $this->assertEquals("1442181600", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->ansiDateToTimestamp("2015-09-14", true));
    }

    public function testGetFromBdd()
    {
        $this->assertEquals('foo', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->getFromBDD('foo'));
    }

    public function testEscapeForXML()
    {
        $this->assertEquals('\\\"foo\\\"', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->escapeForXML('\"foo\"'));
    }

    public function testGetFromXMLElt()
    {
        $this->assertEquals("école", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->getFromXMLElt("école"));
    }

    public function testTruncateString()
    {
        $this->assertEquals("foo...", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->truncateString("foobar", 3, true));
    }

    public function testGetPrettyHours()
    {
        $this->assertEquals("07h 22min 42s", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getPrettyHours("07:22:42"));
    }

    public function testGetPrettyHoursFailed()
    {
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getPrettyHours("foo"));
    }


    public function testGetTimestampFromBDDDate()
    {
        $this->assertEquals("1442208162", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getTimestampFromBDDDate("2015-09-14 07:22:42"));
    }

    public function testGetTimestampFromBDDDateFailed()
    {
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getTimestampFromBDDDate("foo"));
    }

    public function testGetDateFromBDDDate()
    {
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getDateFromBDDDate("foo"));
    }

    public function testGetDateFromBDDDateOK()
    {
        $this->assertEquals("14 septembre 2015 à 07h22min42s", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getDateFromBDDDate("2015-09-14 07:22:42", true));
    }

    public function testGetDateFromBDDBeginningOfYear()
    {
        $this->assertEquals("1 janvier 2022 à 00h00min00s", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getDateFromBDDDate("2022-01-01 00:00:00", true));
    }

    public function testGetANSIDateFromBDDDate()
    {
        $this->assertEquals("2015-09-14", \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getANSIDateFromBDDDate("2015-09-14 07:22:42"));
    }

    public function testGetANSIDateFromBDDDateFailed()
    {
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getANSIDateFromBDDDate("foo"));
    }


    public function testCreateDirTree()
    {
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        $dir_to_create = "foo/bar/baz";
        $this->assertTrue(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FichierHelper::class)->createDirTree($testStreamUrl . "/" . $dir_to_create, $testStreamUrl));
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
