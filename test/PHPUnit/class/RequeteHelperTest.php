<?php

namespace S2low\Helpers;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use UnexpectedValueException;

class RequeteHelperTest extends TestCase
{
    private RequeteHelper $requeteHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_GET = [];
        $_REQUEST = [];
        $_FILES = [];
        $_SESSION = [];
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['PHP_SELF'] = '';

        $sessionHelper = new SessionHelper();
        $formatHelper = new FormatHelper();
        $dateHelper = new DateHelper();
        $this->requeteHelper = new RequeteHelper($sessionHelper, $formatHelper, $dateHelper);
    }

    /**
     * Aide pour définir l'exception attendue (wrapper de compatibilité)
     */
    private function setExpectedException(string $exceptionClass, string $message)
    {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($message);
    }

    // ==========================================
    // Méthodes d'entrée de requête / Superglobales
    // ==========================================

    public function testGetFilesNormal()
    {
        $_FILES['test_file'] = [
            'name' => 'document_é.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/phpabc123',
            'error' => 0,
            'size' => 12345
        ];

        $res = $this->requeteHelper->getFiles('test_file');
        $this->assertSame('document_é.pdf', $res['name']);
        $this->assertSame(12345, $res['size']);
    }

    public function testGetFilesApiCall()
    {
        $_FILES['test_file'] = [
            'name' => mb_convert_encoding('document_é.pdf', 'ISO-8859-1', 'UTF-8'),
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/phpabc123',
            'error' => 0,
            'size' => 12345
        ];

        $_POST['api'] = 1;

        $res = $this->requeteHelper->getFiles('test_file', true);
        $this->assertSame('document_é.pdf', $res['name']);
    }

    public function testGetFilesFromArrayNormal()
    {
        $_FILES['test_files'] = [
            'name' => ['file1_é.pdf', 'file2.pdf'],
            'type' => ['application/pdf', 'application/pdf'],
            'tmp_name' => ['/tmp/php1', '/tmp/php2'],
            'error' => [0, 0],
            'size' => [100, 200]
        ];

        $res = $this->requeteHelper->getFilesFromArray('test_files');
        $this->assertSame('file1_é.pdf', $res['name'][0]);
        $this->assertSame(200, $res['size'][1]);
    }

    public function testGetFilesFromArrayApiCall()
    {
        $_FILES['test_files'] = [
            'name' => [
                mb_convert_encoding('file1_é.pdf', 'ISO-8859-1', 'UTF-8'),
                'file2.pdf'
            ],
            'type' => ['application/pdf', 'application/pdf'],
            'tmp_name' => ['/tmp/php1', '/tmp/php2'],
            'error' => [0, 0],
            'size' => [100, 200]
        ];

        $_POST['api'] = 1;

        $res = $this->requeteHelper->getFilesFromArray('test_files', true);
        $this->assertSame('file1_é.pdf', $res['name'][0]);
        $this->assertSame('file2.pdf', $res['name'][1]);
    }

    public function testGetVarFromPostNormal()
    {
        $_POST['foo'] = 'bar';
        $this->assertSame('bar', $this->requeteHelper->getVarFromPost('foo'));
    }

    public function testGetVarFromPostApiCall()
    {
        $_POST['api'] = 1;
        $_POST['foo'] = mb_convert_encoding('value_é', 'ISO-8859-1', 'UTF-8');

        $this->assertSame('value_é', $this->requeteHelper->getVarFromPost('foo', false, true));
    }

    public function testGetVarFromPostMemorize()
    {
        $_POST['foo'] = 'bar';
        $this->requeteHelper->getVarFromPost('foo', true);
        $this->assertSame('bar', $_SESSION['temp']['foo']);
    }

    public function testGetIntFromPost()
    {
        $_POST['age'] = '42';
        $this->assertSame('42', $this->requeteHelper->getIntFromPost('age'));
    }

    public function testGetIntFromPostNullable()
    {
        $_POST['age'] = '';
        $this->assertSame('', $this->requeteHelper->getIntFromPost('age', true));

        unset($_POST['age']);
        $this->assertNull($this->requeteHelper->getIntFromPost('age', true));
    }

    public function testGetIntFromPostException()
    {
        $_POST['age'] = 'not-an-int';
        $this->setExpectedException(UnexpectedValueException::class, "age n'est pas un entier");
        $this->requeteHelper->getIntFromPost('age');
    }

    public function testGetVarFromGet()
    {
        $_GET['param'] = 'val';
        $this->assertSame('val', $this->requeteHelper->getVarFromGet('param'));
    }

    public function testGetIntFromGet()
    {
        $_GET['num'] = '100';
        $this->assertSame('100', $this->requeteHelper->getIntFromGet('num'));
    }

    public function testGetIntFromGetException()
    {
        $_GET['num'] = 'abc';
        $this->setExpectedException(UnexpectedValueException::class, "num n'est pas un entier");
        $this->requeteHelper->getIntFromGet('num');
    }

    public function testGetDateFromGet()
    {
        $_GET['date'] = '2026-06-17';
        $this->assertSame('2026-06-17', $this->requeteHelper->getDateFromGet('date'));
    }

    public function testGetDateFromGetNullable()
    {
        $_GET['date'] = '';
        $this->assertSame('', $this->requeteHelper->getDateFromGet('date', true));

        unset($_GET['date']);
        $this->assertNull($this->requeteHelper->getDateFromGet('date', true));
    }

    public function testGetDateFromGetException()
    {
        $_GET['date'] = 'invalid-date';
        $this->setExpectedException(UnexpectedValueException::class, "date n'est pas une date");
        $this->requeteHelper->getDateFromGet('date');
    }

    public function testGetVarFromRequestPost()
    {
        $_POST['data'] = 'post_data';
        $this->assertSame('post_data', $this->requeteHelper->getVarFromRequest('data', 'POST'));
    }

    public function testGetVarFromRequestGetArray()
    {
        $_GET['list'] = ['item1', 'item2'];
        $this->assertSame(['item1', 'item2'], $this->requeteHelper->getVarFromRequest('list', 'GET'));
    }

    // ==========================================
    // Méthodes de Redirection / Sortie
    // ==========================================

    public function testReturnAndExitApiSuccess()
    {
        $_GET['api'] = 1;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Success message');
        $this->expectOutputString("OK\nSuccess message\n");

        $this->requeteHelper->returnAndExit(0, 'Success message');
    }

    public function testReturnAndExitApiFailure()
    {
        $_GET['api'] = 1;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error message');
        $this->expectOutputString("KO\nError message\n");

        $this->requeteHelper->returnAndExit(1, 'Error message');
    }

    public function testReturnAndExitApiCustomMessage()
    {
        $_GET['api'] = 1;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Web message');
        $this->expectOutputString("OK\nAPI Custom Message\n");

        $this->requeteHelper->returnAndExit(0, 'Web message', null, 'API Custom Message');
    }

    public function testReturnAndExitWebException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Web Error');
        $this->expectOutputString("Web Error\n");

        $this->requeteHelper->returnAndExit(0, 'Web Error');
    }

    public function testReturnAndExitWebRedirect()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Message : Web Error');

        $this->requeteHelper->returnAndExit(0, 'Web Error', '/redirect/url');
    }

    public function testExitOrDisplayErrorApi()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Exit !');

        $this->expectOutputRegex('/"status":"error"/');
        $this->expectOutputRegex('/"error-message":"API error msg"/');

        $this->requeteHelper->exitOrDisplayError(true, 'API error msg', '/fallback');
    }

    public function testExitOrDisplayErrorWeb()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');

        $this->expectOutputString("header('Location: /redirect/here','1','0') called\n");

        $this->requeteHelper->exitOrDisplayError(false, 'Web error msg', '/redirect/here');
    }

    public function testGetURLWithParam()
    {
        $_SERVER['PHP_SELF'] = '/script.php';
        $_SERVER['QUERY_STRING'] = 'a=1&b=2';

        $expected = trim(WEBSITE_SSL, '/') . '/script.php?a=1&amp;b=2&amp;c=3';
        $this->assertSame($expected, $this->requeteHelper->getURLWithParam(['c' => 3]));

        // Écrase le paramètre
        $expectedOverwrite = trim(WEBSITE_SSL, '/') . '/script.php?b=2&amp;a=5';
        $this->assertSame($expectedOverwrite, $this->requeteHelper->getURLWithParam(['a' => 5]));
    }

    public function testGetLink()
    {
        $expected = trim(WEBSITE_SSL, '/') . '/my/relative/path';
        $this->assertSame($expected, $this->requeteHelper->getLink('/my/relative/path'));
        $this->assertSame($expected, $this->requeteHelper->getLink('my/relative/path'));
    }

    // Original basic tests from HelpersTest

    public function testGetVarFromPostBasic()
    {
        $_POST = array('foo' => 'bar');
        $this->assertEquals('bar', $this->requeteHelper->getVarFromPost('foo'));
    }

    public function testGetVarFromGetBasic()
    {
        $_GET = array('foo' => 'bar');
        $this->assertEquals('bar', $this->requeteHelper->getVarFromGet('foo'));
    }

    public function testGetVarFromRequestBasic()
    {
        $_POST = array('foo' => 'bar');
        $this->assertEquals('bar', $this->requeteHelper->getVarFromRequest('foo', 'POST'));
    }

    public function testGetVarFromRequestGetBasic()
    {
        $_GET = array('foo' => 'bar');
        $this->assertEquals('bar', $this->requeteHelper->getVarFromRequest('foo', 'GET'));
    }

    public function testGetVarFromRequestArrayBasic()
    {
        $_GET = array('foo' => array('bar','baz'));
        $this->assertEquals(array('bar','baz'), $this->requeteHelper->getVarFromRequest('foo', 'GET'));
    }

    public function testGetVarFromRequestPutInSessionBasic()
    {
        $_GET = array('foo' => 'bar');
        $this->assertEquals('bar', $this->requeteHelper->getVarFromRequest('foo', 'GET', true));
    }

    public function testReturnAndExitBasic()
    {
        $_GET['api'] = 1;
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("OK\nfoo\n");
        $this->requeteHelper->returnAndExit(0, "foo");
    }

    public function testReturnAndExitApiMessageBasic()
    {
        $_GET['api'] = 1;
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("OK\nbaz\n");
        $this->requeteHelper->returnAndExit(0, "foo", null, "baz");
    }

    public function testReturnAndExitNoRedirBasic()
    {
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("foo\n");
        $this->requeteHelper->returnAndExit(0, "foo");
    }

    public function testReturnAndExitRedirBasic()
    {
        $this->setExpectedException("Exception", "foo");
        $this->requeteHelper->returnAndExit(0, "foo", true);
    }

    public function testReturnAndExitFailedBasic()
    {
        $_GET['api'] = 1;
        $this->setExpectedException("Exception", "foo");
        $this->expectOutputString("KO\nfoo\n");
        $this->requeteHelper->returnAndExit(1, "foo");
    }

    public function testNullIntFromPost()
    {
        $this->assertEquals(
            null,
            $this->requeteHelper->getIntFromPost("test", true)
        );
    }

    public function testNullExceptionIntFromPost()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("test est null");
        $this->requeteHelper->getIntFromPost("test", false);
    }
}
