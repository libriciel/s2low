<?php

use S2lowLegacy\Class\Helpers;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_GET = [];
        $_REQUEST = [];
        $_SESSION = [];
    }

    /** @deprecated  */
    public function setExpectedException(string $e, string $message)
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }

    public function getFilesProvider()
    {
        return [
            // [$_FILES, $_POST, $_GET, $allowGetApiCall, $expectedResult]
            [
                ['my_file' => ['name' => 'test.txt', 'type' => 'text/plain']],
                [],
                [],
                false,
                ['name' => 'test.txt', 'type' => 'text/plain']
            ],
            [
                ['my_file' => ['name' => 'testé.txt', 'type' => 'text/plain']],
                ['api' => '1'],
                [],
                false,
                ['name' => mb_convert_encoding('testé.txt', 'UTF-8', 'ISO-8859-1'), 'type' => 'text/plain']
            ],
            [
                ['my_file' => ['name' => 'testé.txt', 'type' => 'text/plain']],
                [],
                ['api' => '1'],
                false,
                ['name' => 'testé.txt', 'type' => 'text/plain']
            ],
            [
                ['my_file' => ['name' => 'testé.txt', 'type' => 'text/plain']],
                [],
                ['api' => '1'],
                true,
                ['name' => mb_convert_encoding('testé.txt', 'UTF-8', 'ISO-8859-1'), 'type' => 'text/plain']
            ],
        ];
    }

    /**
     * @dataProvider getFilesProvider
     */
    public function testGetFiles($files, $post, $get, $allowGetApiCall, $expected)
    {
        $_FILES = $files;
        $_POST = $post;
        $_GET = $get;
        $this->assertEquals($expected, Helpers::getFiles('my_file', $allowGetApiCall));
    }

    public function getFilesFromArrayProvider()
    {
        return [
            [
                ['my_files' => ['name' => ['file1.txt', 'file2.txt'], 'type' => ['text/plain', 'text/plain']]],
                [],
                [],
                false,
                ['name' => ['file1.txt', 'file2.txt'], 'type' => ['text/plain', 'text/plain']]
            ],
            [
                ['my_files' => ['name' => ['testé1.txt', 'testé2.txt'], 'type' => ['text/plain', 'text/plain']]],
                ['api' => '1'],
                [],
                false,
                ['name' => [mb_convert_encoding('testé1.txt', 'UTF-8', 'ISO-8859-1'), mb_convert_encoding('testé2.txt', 'UTF-8', 'ISO-8859-1')], 'type' => ['text/plain', 'text/plain']]
            ],
        ];
    }

    /**
     * @dataProvider getFilesFromArrayProvider
     */
    public function testGetFilesFromArray($files, $post, $get, $allowGetApiCall, $expected)
    {
        $_FILES = $files;
        $_POST = $post;
        $_GET = $get;
        $this->assertEquals($expected, Helpers::getFilesFromArray('my_files', $allowGetApiCall));
    }

    public function getVarFromPostProvider()
    {
        return [
            // [$post, $name, $memorize, $allowGetApiCall, $expectedResult, $expectedSession]
            [['foo' => 'bar'], 'foo', false, false, 'bar', null],
            [['foo' => 'bar'], 'foo', true, false, 'bar', 'bar'],
            [['foo' => 'bàr', 'api' => '1'], 'foo', false, false, mb_convert_encoding('bàr', 'UTF-8', 'ISO-8859-1'), null],
            [[], 'foo', false, false, null, null],
        ];
    }

    /**
     * @dataProvider getVarFromPostProvider
     */
    public function testGetVarFromPost($post, $name, $memorize, $allowGetApiCall, $expectedResult, $expectedSession)
    {
        $_POST = $post;
        $this->assertEquals($expectedResult, Helpers::getVarFromPost($name, $memorize, $allowGetApiCall));
        if ($expectedSession !== null) {
            $this->assertEquals($expectedSession, $_SESSION['temp'][$name]);
        }
    }

    public function getIntFromPostProvider()
    {
        return [
            // [$post, $name, $nullable, $memorize, $expectedResult, $expectException]
            [['foo' => '42'], 'foo', false, false, '42', false],
            [['foo' => '0'], 'foo', false, false, '0', false],
            [['foo' => ''], 'foo', true, false, '', false],
            [[], 'foo', true, false, null, false],
            [[], 'foo', false, false, null, true],
            [['foo' => 'not_an_int'], 'foo', false, false, null, true],
        ];
    }

    /**
     * @dataProvider getIntFromPostProvider
     */
    public function testGetIntFromPost($post, $name, $nullable, $memorize, $expectedResult, $expectException)
    {
        $_POST = $post;
        if ($expectException) {
            $this->expectException(UnexpectedValueException::class);
        }
        $result = Helpers::getIntFromPost($name, $nullable, $memorize);
        if (!$expectException) {
            $this->assertEquals($expectedResult, $result);
            if ($memorize) {
                $this->assertEquals($expectedResult, $_SESSION['temp'][$name]);
            }
        }
    }

    public function getVarFromGetProvider()
    {
        return [
            // [$get, $name, $memorize, $expectedResult, $expectedSession]
            [['foo' => 'bar'], 'foo', false, 'bar', null],
            [['foo' => 'bar'], 'foo', true, 'bar', 'bar'],
            [[], 'foo', false, null, null],
        ];
    }

    /**
     * @dataProvider getVarFromGetProvider
     */
    public function testGetVarFromGet($get, $name, $memorize, $expectedResult, $expectedSession)
    {
        $_GET = $get;
        $this->assertEquals($expectedResult, Helpers::getVarFromGet($name, $memorize));
        if ($expectedSession !== null) {
            $this->assertEquals($expectedSession, $_SESSION['temp'][$name]);
        }
    }

    public function getIntFromGetProvider()
    {
        return [
            // [$get, $name, $nullable, $expectedResult, $expectException]
            [['foo' => '123'], 'foo', false, '123', false],
            [[], 'foo', true, null, false],
            [[], 'foo', false, null, true],
            [['foo' => 'abc'], 'foo', false, null, true],
        ];
    }

    /**
     * @dataProvider getIntFromGetProvider
     */
    public function testGetIntFromGet($get, $name, $nullable, $expectedResult, $expectException)
    {
        $_GET = $get;
        if ($expectException) {
            $this->expectException(UnexpectedValueException::class);
        }
        $result = Helpers::getIntFromGet($name, $nullable);
        if (!$expectException) {
            $this->assertEquals($expectedResult, $result);
        }
    }

    public function getDateFromGetProvider()
    {
        return [
            // [$get, $name, $nullable, $expectedResult, $expectException]
            [['foo' => '2026-06-17'], 'foo', false, '2026-06-17', false],
            [[], 'foo', true, null, false],
            [[], 'foo', false, null, true],
            [['foo' => 'not-a-date'], 'foo', false, null, true],
        ];
    }

    /**
     * @dataProvider getDateFromGetProvider
     */
    public function testGetDateFromGet($get, $name, $nullable, $expectedResult, $expectException)
    {
        $_GET = $get;
        if ($expectException) {
            $this->expectException(UnexpectedValueException::class);
        }
        $result = Helpers::getDateFromGet($name, $nullable);
        if (!$expectException) {
            $this->assertEquals($expectedResult, $result);
        }
    }

    public function getVarFromRequestProvider()
    {
        return [
            // [$post, $get, $name, $type, $memorize, $expectedResult, $expectedSession]
            [['foo' => 'bar'], [], 'foo', 'POST', false, 'bar', null],
            [[], ['foo' => 'bar'], 'foo', 'GET', false, 'bar', null],
            [['foo' => ['bar', 'baz']], [], 'foo', 'POST', false, ['bar', 'baz'], null],
            [['foo' => 'bar'], [], 'foo', 'POST', true, 'bar', 'bar'],
        ];
    }

    /**
     * @dataProvider getVarFromRequestProvider
     */
    public function testGetVarFromRequest($post, $get, $name, $type, $memorize, $expectedResult, $expectedSession)
    {
        $_POST = $post;
        $_GET = $get;
        $this->assertEquals($expectedResult, Helpers::getVarFromRequest($name, $type, $memorize));
        if ($expectedSession !== null) {
            $this->assertEquals($expectedSession, $_SESSION['temp'][$name]);
        }
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

    public function timestampToStringProvider()
    {
        return [
            [1442224800, '14 septembre 2015'],
            [1640991600, '1 janvier 2021'], // due to week-year YYYY formatting
            [1654034400, '1 juin 2022'],    // mid-year case
        ];
    }

    /**
     * @dataProvider timestampToStringProvider
     */
    public function testTimestampToString($timestamp, $expected)
    {
        $this->assertEquals($expected, Helpers::TimestampToString($timestamp));
    }

    public function testGetFromBdd()
    {
        $this->assertEquals('foo', Helpers::getFromBDD('foo'));
    }

    public function escapeForXMLProvider()
    {
        return [
            ['"test"', '\\"test\\"'],
            ['hello "world"', 'hello \\"world\\"'],
            [null, ''],
        ];
    }

    /**
     * @dataProvider escapeForXMLProvider
     */
    public function testEscapeForXML($input, $expected)
    {
        $this->assertEquals($expected, Helpers::escapeForXML($input));
    }

    public function testGetFromXMLElt()
    {
        $this->assertEquals("école", Helpers::getFromXMLElt("école"));
        $xml = new SimpleXMLElement('<element>école</element>');
        $this->assertEquals("école", Helpers::getFromXMLElt($xml));
    }

    public function truncateStringProvider()
    {
        return [
            ['foobar', 3, true, 'foo...'],
            ['foobar', 3, false, 'foo'],
            ['foobar', 10, true, 'foobar'],
            ['école', 3, true, 'éco...'],
        ];
    }

    /**
     * @dataProvider truncateStringProvider
     */
    public function testTruncateString($str, $length, $add_ellipsis, $expected)
    {
        $this->assertEquals($expected, Helpers::truncateString($str, $length, $add_ellipsis));
    }

    public function getPrettyHoursProvider()
    {
        return [
            ['07:22:42', '07h 22min 42s'],
            ['12:00:00', '12h 00min 00s'],
            ['invalid', null],
        ];
    }

    /**
     * @dataProvider getPrettyHoursProvider
     */
    public function testGetPrettyHours($hour, $expected)
    {
        $this->assertEquals($expected, Helpers::getPrettyHours($hour));
    }

    public function getTimestampFromBDDDataProvider()
    {
        return [
            ['2015-09-14 07:22:42', '1442208162'],
            ['2022-01-01 00:00:00', '1640991600'],
            ['invalid', null],
            [null, null],
        ];
    }

    /**
     * @dataProvider getTimestampFromBDDDataProvider
     */
    public function testGetTimestampFromBDDDate($date, $expected)
    {
        if ($expected !== null) {
            $this->assertEquals($expected, Helpers::getTimestampFromBDDDate($date));
        } else {
            $this->assertNull(Helpers::getTimestampFromBDDDate($date));
        }
    }

    public function getDateFromBDDDataProvider()
    {
        return [
            ['2015-09-14 07:22:42', true, '14 septembre 2015 à 07h22min42s'],
            ['2015-09-14 07:22:42', false, '14 septembre 2015'],
            ['2022-01-01 00:00:00', true, '1 janvier 2022 à 00h00min00s'],
            ['2022-01-01 00:00:00', false, '1 janvier 2022'],
            ['invalid', false, null],
        ];
    }

    /**
     * @dataProvider getDateFromBDDDataProvider
     */
    public function testGetDateFromBDDDate($date, $with_hours, $expected)
    {
        $this->assertEquals($expected, Helpers::getDateFromBDDDate($date, $with_hours));
    }

    public function getANSIDateFromBDDDataProvider()
    {
        return [
            ['2015-09-14 07:22:42', '2015-09-14'],
            ['2022-01-01 00:00:00', '2022-01-01'],
            ['invalid', null],
        ];
    }

    /**
     * @dataProvider getANSIDateFromBDDDataProvider
     */
    public function testGetANSIDateFromBDDDate($date, $expected)
    {
        $this->assertEquals($expected, Helpers::getANSIDateFromBDDDate($date));
    }

    public function getURLWithParamProvider()
    {
        return [
            ['', '/index.php', ['foo' => 'bar'], '?foo=bar'],
            ['foo=baz', '/index.php', ['foo' => 'bar'], '?foo=bar'],
            ['a=1&b=2', '/index.php', ['a' => '3'], '?b=2&amp;a=3'],
            ['a=1&b=2', '/index.php', ['a' => '3', 'c' => '4'], '?b=2&amp;a=3&amp;c=4'],
        ];
    }

    /**
     * @dataProvider getURLWithParamProvider
     */
    public function testGetURLWithParam($queryString, $phpSelf, $params, $expectedRelativeUrl)
    {
        $_SERVER['QUERY_STRING'] = $queryString;
        $_SERVER['PHP_SELF'] = $phpSelf;
        $this->assertEquals(Helpers::getLink($phpSelf . $expectedRelativeUrl), Helpers::getURLWithParam($params));
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

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
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

    public function chunkStringProvider()
    {
        return [
            ['hello', 2, 'he'],
            ['01234567890123456789012345678901234567890', 10, '0123456789...'],
        ];
    }

    /**
     * @dataProvider chunkStringProvider
     */
    public function testChunkString($string, $length, $expected)
    {
        $this->assertEquals($expected, Helpers::chunkString($string, $length));
    }

    public function getLinkProvider()
    {
        return [
            ['/test', trim(WEBSITE_SSL, '/') . '/test'],
            ['test/sub', trim(WEBSITE_SSL, '/') . '/test/sub'],
        ];
    }

    /**
     * @dataProvider getLinkProvider
     */
    public function testGetLink($relativePath, $expected)
    {
        $this->assertEquals($expected, Helpers::getLink($relativePath));
    }

    public function testExitOrDisplayErrorApi()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Exit !");
        $this->expectOutputString("header('Content-type: text/plain','1','0') called\n" . '{"status":"error","error-message":"test"}');
        Helpers::exitOrDisplayError(true, "test", "http://redirect");
    }

    public function testExitOrDisplayErrorRedirect()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("exit() called");
        Helpers::exitOrDisplayError(false, "test", "http://redirect");
        $this->assertEquals("test", $_SESSION['error']);
    }
}
