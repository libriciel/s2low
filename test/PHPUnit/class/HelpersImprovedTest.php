<?php

use S2lowLegacy\Class\Helpers;
use PHPUnit\Framework\TestCase;

class HelpersImprovedTest extends TestCase
{
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

        $res = Helpers::getFiles('test_file');
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

        // Déclenche isApiCall en définissant la variable POST api à 1
        $_POST['api'] = 1;

        $res = Helpers::getFiles('test_file', true);
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

        $res = Helpers::getFilesFromArray('test_files');
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

        $res = Helpers::getFilesFromArray('test_files', true);
        $this->assertSame('file1_é.pdf', $res['name'][0]);
        $this->assertSame('file2.pdf', $res['name'][1]);
    }

    public function testGetVarFromPostNormal()
    {
        $_POST['foo'] = 'bar';
        $this->assertSame('bar', Helpers::getVarFromPost('foo'));
    }

    public function testGetVarFromPostApiCall()
    {
        $_POST['api'] = 1;
        $_POST['foo'] = mb_convert_encoding('value_é', 'ISO-8859-1', 'UTF-8');

        $this->assertSame('value_é', Helpers::getVarFromPost('foo', false, true));
    }

    public function testGetVarFromPostMemorize()
    {
        $_POST['foo'] = 'bar';
        Helpers::getVarFromPost('foo', true);
        $this->assertSame('bar', $_SESSION['temp']['foo']);
    }

    public function testGetIntFromPost()
    {
        $_POST['age'] = '42';
        $this->assertSame('42', Helpers::getIntFromPost('age'));
    }

    public function testGetIntFromPostNullable()
    {
        $_POST['age'] = '';
        $this->assertSame('', Helpers::getIntFromPost('age', true));

        unset($_POST['age']);
        $this->assertNull(Helpers::getIntFromPost('age', true));
    }

    public function testGetIntFromPostException()
    {
        $_POST['age'] = 'not-an-int';
        $this->setExpectedException(UnexpectedValueException::class, "age n'est pas un entier");
        Helpers::getIntFromPost('age');
    }

    public function testGetVarFromGet()
    {
        $_GET['param'] = 'val';
        $this->assertSame('val', Helpers::getVarFromGet('param'));
    }

    public function testGetIntFromGet()
    {
        $_GET['num'] = '100';
        $this->assertSame('100', Helpers::getIntFromGet('num'));
    }

    public function testGetIntFromGetException()
    {
        $_GET['num'] = 'abc';
        $this->setExpectedException(UnexpectedValueException::class, "num n'est pas un entier");
        Helpers::getIntFromGet('num');
    }

    public function testGetDateFromGet()
    {
        $_GET['date'] = '2026-06-17';
        $this->assertSame('2026-06-17', Helpers::getDateFromGet('date'));
    }

    public function testGetDateFromGetNullable()
    {
        $_GET['date'] = '';
        $this->assertSame('', Helpers::getDateFromGet('date', true));

        unset($_GET['date']);
        $this->assertNull(Helpers::getDateFromGet('date', true));
    }

    public function testGetDateFromGetException()
    {
        $_GET['date'] = 'invalid-date';
        $this->setExpectedException(UnexpectedValueException::class, "date n'est pas une date");
        Helpers::getDateFromGet('date');
    }

    public function testGetVarFromRequestPost()
    {
        $_POST['data'] = 'post_data';
        $this->assertSame('post_data', Helpers::getVarFromRequest('data', 'POST'));
    }

    public function testGetVarFromRequestGetArray()
    {
        $_GET['list'] = ['item1', 'item2'];
        $this->assertSame(['item1', 'item2'], Helpers::getVarFromRequest('list', 'GET'));
    }

    // ==========================================
    // Méthodes de Session
    // ==========================================

    public function testSessionLifecycle()
    {
        Helpers::putInSession('key1', 'value1');
        $this->assertSame('value1', $_SESSION['temp']['key1']);

        // Test de récupération avec delete=false
        $this->assertSame('value1', Helpers::getFromSession('key1', false));
        $this->assertSame('value1', $_SESSION['temp']['key1']);

        // Test de récupération avec delete=true (par défaut)
        $this->assertSame('value1', Helpers::getFromSession('key1'));
        $this->assertNull(Helpers::getFromSession('key1'));
        $this->assertFalse(isset($_SESSION['temp']['key1']));

        // Test de purge
        Helpers::putInSession('key2', 'value2');
        Helpers::purgeTempSession();
        $this->assertNull(Helpers::getFromSession('key2'));
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

        Helpers::returnAndExit(0, 'Success message');
    }

    public function testReturnAndExitApiFailure()
    {
        $_GET['api'] = 1;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error message');
        $this->expectOutputString("KO\nError message\n");

        Helpers::returnAndExit(1, 'Error message');
    }

    public function testReturnAndExitApiCustomMessage()
    {
        $_GET['api'] = 1;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Web message');
        $this->expectOutputString("OK\nAPI Custom Message\n");

        Helpers::returnAndExit(0, 'Web message', null, 'API Custom Message');
    }

    public function testReturnAndExitWebException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Web Error');
        $this->expectOutputString("Web Error\n");

        Helpers::returnAndExit(0, 'Web Error');
    }

    public function testReturnAndExitWebRedirect()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Message : Web Error');

        Helpers::returnAndExit(0, 'Web Error', '/redirect/url');
    }

    public function testExitOrDisplayErrorApi()
    {
        // Test du chemin api = true (qui appelle JSONoutput::displayErrorAndExit)
        // Sous TESTING_ENVIRONNEMENT, cela va afficher le JSON et lever une Exception("Exit !")
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Exit !');

        // Capture de la sortie standard
        $this->expectOutputRegex('/"status":"error"/');
        $this->expectOutputRegex('/"error-message":"API error msg"/');

        Helpers::exitOrDisplayError(true, 'API error msg', '/fallback');
    }

    public function testExitOrDisplayErrorWeb()
    {
        // Test du chemin api = false
        // Sous TESTING_ENVIRONNEMENT, cela doit appeler header_wrapper et exit_wrapper (qui lève une Exception)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');

        $this->expectOutputString("header('Location: /redirect/here','1','0') called\n");

        Helpers::exitOrDisplayError(false, 'Web error msg', '/redirect/here');
    }

    // ==========================================
    // Méthodes de Date & Timestamp
    // ==========================================

    public function testAnsiDateToTimestamp()
    {
        // Heure Europe/Paris 2026-06-17 12:00:00
        // Le 2026-06-17 est en heure d'été (UTC+2) -> 12:00:00 heure locale correspond à 10:00:00 UTC
        $expected = strtotime('2026-06-17 12:00:00 Europe/Paris');
        $this->assertSame($expected, Helpers::ansiDateToTimestamp('2026-06-17'));
    }

    public function testAnsiDateToTimestampAtMidnight()
    {
        // Heure Europe/Paris 2026-06-17 00:00:00
        $expected = strtotime('2026-06-17 00:00:00 Europe/Paris');
        $this->assertSame($expected, Helpers::ansiDateToTimestamp('2026-06-17', true));
    }

    public function testTimestampToString()
    {
        // Teste avec un timestamp fixe en Europe/Paris
        $timestamp = strtotime('2015-09-14 12:00:00 Europe/Paris');
        // Formatage de la date en français : 14 septembre 2015
        $this->assertSame('14 septembre 2015', Helpers::TimestampToString($timestamp));
    }

    public function testGetFromBDD()
    {
        $this->assertSame('any_db_value', Helpers::getFromBDD('any_db_value'));
    }

    public function testEscapeForXML()
    {
        // Nous vérifions le comportement réel du code (échappement de " en \")
        // bien que cela soit techniquement incorrect pour du XML standard.
        $this->assertSame('Hello \\"World\\"', Helpers::escapeForXML('Hello "World"'));
        $this->assertSame('', Helpers::escapeForXML(null));
    }

    public function testGetFromXMLElt()
    {
        $elt = new SimpleXMLElement('<node>valeur_é</node>');
        $this->assertSame('valeur_é', Helpers::getFromXMLElt($elt));
    }

    public function testTruncateString()
    {
        $str = 'Un texte assez long pour dépasser la limite';
        $this->assertSame('Un texte...', Helpers::truncateString($str, 8, true));
        $this->assertSame('Un texte', Helpers::truncateString($str, 8, false));

        // Chaîne plus courte que la longueur max
        $this->assertSame('Court', Helpers::truncateString('Court', 10, true));
    }

    public function testGetPrettyHours()
    {
        $this->assertSame('14h 05min 32s', Helpers::getPrettyHours('14:05:32'));
        $this->assertNull(Helpers::getPrettyHours('invalid'));
    }

    public function testGetTimestampFromBDDDate()
    {
        $dateStr = '2015-09-14 07:22:42';
        $expected = strtotime($dateStr . ' Europe/Paris');
        $this->assertSame($expected, Helpers::getTimestampFromBDDDate($dateStr));
        $this->assertNull(Helpers::getTimestampFromBDDDate('invalid-date'));
    }

    public function testGetDateFromBDDDate()
    {
        $dateStr = '2015-09-14 07:22:42';
        // Avec hours = false
        $this->assertSame('14 septembre 2015', Helpers::getDateFromBDDDate($dateStr, false));

        // Avec hours = true
        $this->assertSame('14 septembre 2015 à 07h22min42s', Helpers::getDateFromBDDDate($dateStr, true));

        // Invalide
        $this->assertNull(Helpers::getDateFromBDDDate('invalid-date'));
    }

    public function testGetANSIDateFromBDDDate()
    {
        $dateStr = '2015-09-14 07:22:42';
        $this->assertSame('2015-09-14', Helpers::getANSIDateFromBDDDate($dateStr));
        $this->assertNull(Helpers::getANSIDateFromBDDDate('invalid'));
    }

    public function testGetURLWithParam()
    {
        $_SERVER['PHP_SELF'] = '/script.php';
        $_SERVER['QUERY_STRING'] = 'a=1&b=2';

        $expected = trim(WEBSITE_SSL, '/') . '/script.php?a=1&amp;b=2&amp;c=3';
        $this->assertSame($expected, Helpers::getURLWithParam(['c' => 3]));

        // Écrase le paramètre
        $expectedOverwrite = trim(WEBSITE_SSL, '/') . '/script.php?b=2&amp;a=5';
        $this->assertSame($expectedOverwrite, Helpers::getURLWithParam(['a' => 5]));
    }

    // ==========================================
    // Méthodes de Système de Fichiers
    // ==========================================

    public function testCreateDirTreeSuccess()
    {
        org\bovigo\vfs\vfsStream::setup('root');
        $rootUrl = org\bovigo\vfs\vfsStream::url('root');

        $path = $rootUrl . '/uploads/actes/2026/06';
        $this->assertTrue(Helpers::createDirTree($path, $rootUrl));
        $this->assertTrue(is_dir($path));
    }

    public function testCreateDirTreeMismatchBase()
    {
        org\bovigo\vfs\vfsStream::setup('root');
        $rootUrl = org\bovigo\vfs\vfsStream::url('root');

        // Chemin complètement en dehors du répertoire de base
        $path = '/some/other/path/entirely';
        $this->assertFalse(Helpers::createDirTree($path, $rootUrl));
    }

    public function testCreateDirTreeFileExists()
    {
        org\bovigo\vfs\vfsStream::setup('root');
        $rootUrl = org\bovigo\vfs\vfsStream::url('root');

        // Crée un fichier là où nous voulons créer un répertoire
        $filePath = $rootUrl . '/blocking_file';
        file_put_contents($filePath, 'content');

        $this->assertFalse(Helpers::createDirTree($filePath, $rootUrl));
    }

    public function testDeleteFromFS()
    {
        org\bovigo\vfs\vfsStream::setup('root');
        $rootUrl = org\bovigo\vfs\vfsStream::url('root');

        $file1 = $rootUrl . '/file1.txt';
        $dir1 = $rootUrl . '/subdir';

        file_put_contents($file1, 'data');
        mkdir($dir1);

        $this->assertTrue(file_exists($file1));
        $this->assertTrue(file_exists($dir1));

        $this->assertTrue(Helpers::deleteFromFS($file1, $dir1));

        $this->assertFalse(file_exists($file1));
        $this->assertFalse(file_exists($dir1));
    }

    public function testFixPerms()
    {
        org\bovigo\vfs\vfsStream::setup('root');
        $rootUrl = org\bovigo\vfs\vfsStream::url('root');

        $file = $rootUrl . '/file.txt';
        file_put_contents($file, 'data');

        $this->assertTrue(Helpers::fixPerms($file));

        $nonExistent = $rootUrl . '/missing.txt';
        $this->assertFalse(Helpers::fixPerms($nonExistent));
    }

    public function testGetAuthorizedCACerts()
    {
        org\bovigo\vfs\vfsStream::setup('root');
        $rootUrl = org\bovigo\vfs\vfsStream::url('root');

        // 1. Test répertoire vide
        $this->assertEmpty(Helpers::getAuthorizedCACerts($rootUrl));

        // 2. Test répertoire invalide
        $this->assertEmpty(Helpers::getAuthorizedCACerts($rootUrl . '/non-existent'));

        // 3. Test cas nominal avec des fichiers virtuels
        // On récupère le contenu du certificat de test existant pour éviter de le surcharger en chaîne brute
        $certContent = file_get_contents(__DIR__ . '/fixtures/root_ca.crt');

        // On crée un certificat valide dans le système de fichiers virtuel
        file_put_contents($rootUrl . '/cert.pem', $certContent);

        // On crée un fichier qui n'est pas un certificat (pour tester le filtrage/gestion d'erreur)
        file_put_contents($rootUrl . '/not_a_cert.txt', 'invalid content');

        // On crée un sous-dossier (pour vérifier qu'il est ignoré)
        mkdir($rootUrl . '/subdir');

        $certs = Helpers::getAuthorizedCACerts($rootUrl);

        // Seul le certificat valide doit être détecté et parsé
        $this->assertCount(1, $certs);
        $this->assertSame('ADULLACT-Projet', $certs[0]['subject']['O']);
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testSendFileToBrowser()
    {
        // Exécution dans un processus séparé pour capturer proprement header_wrapper et echo
        $this->expectOutputRegex('/<?php/');
        $this->assertTrue(Helpers::sendFileToBrowser(__FILE__, basename(__FILE__), 'text/x-php'));
    }

    public function testSendFileToBrowserNotFound()
    {
        $this->assertFalse(Helpers::sendFileToBrowser('/non-existent/file.txt', 'file.txt'));
        $this->assertSame('Fichier spécifié introuvable', Helpers::$last_error);
    }

    public function testGenTempName()
    {
        $name1 = Helpers::genTempName();
        $this->assertStringStartsWith('__tmp__', $name1);
        $this->assertSame(15, strlen($name1)); // '__tmp__' fait 7 + 8 chiffres

        $name2 = Helpers::genTempName(10, false);
        $this->assertStringMatchesFormat('%d', $name2);
        $this->assertSame(10, strlen($name2));
    }

    public function testGetFileType()
    {
        $this->assertSame('text/x-php', Helpers::getFileType(__FILE__));
        $this->assertNull(Helpers::getFileType('/non-existent-path'));
    }

    // ==========================================
    // Méthodes de Vérification et Utilitaires
    // ==========================================

    /**
     * @dataProvider checkIntProvider
     */
    public function testCheckInt($val, $nullable, $expectedResult)
    {
        $this->assertSame($expectedResult, Helpers::checkInt($val, $nullable, 'test_param'));
    }

    public function checkIntProvider(): array
    {
        return [
            ['123', false, '123'],
            ['0', false, '0'],
            [null, true, null],
            ['', true, '']
        ];
    }

    /**
     * @dataProvider checkIntProviderException
     */
    public function testCheckIntException($val, $nullable, $expectedMsg)
    {
        $this->setExpectedException(UnexpectedValueException::class, $expectedMsg);
        Helpers::checkInt($val, $nullable, 'test_param');
    }

    public function checkIntProviderException(): array
    {
        return [
            [null, false, 'test_param est null'],
            ['abc', false, "test_param n'est pas un entier"],
            ['12.3', false, "test_param n'est pas un entier"]
        ];
    }

    /**
     * @dataProvider checkDateProvider
     */
    public function testCheckDate($val, $nullable, $expectedResult)
    {
        $this->assertSame($expectedResult, Helpers::checkDate($val, $nullable, 'test_date'));
    }

    public function checkDateProvider(): array
    {
        return [
            ['2026-06-17', false, '2026-06-17'],
            [null, true, null],
            ['', true, '']
        ];
    }

    /**
     * @dataProvider checkDateProviderException
     */
    public function testCheckDateException($val, $nullable, $expectedMsg)
    {
        $this->setExpectedException(UnexpectedValueException::class, $expectedMsg);
        Helpers::checkDate($val, $nullable, 'test_date');
    }

    public function checkDateProviderException(): array
    {
        return [
            [null, false, "test_date n'est pas une date"],
            ['invalid-date-string', false, "test_date n'est pas une date"],
            ['2026-15-40', false, "test_date n'est pas une date"]
        ];
    }

    public function testChunkString()
    {
        $this->assertSame('A brief text', Helpers::chunkString('A brief text', 20));
        $this->assertSame('A very long text that goes on and on and...', Helpers::chunkString('A very long text that goes on and on and exceeds forty', 40));
    }

    public function testGetLink()
    {
        $expected = trim(WEBSITE_SSL, '/') . '/my/relative/path';
        $this->assertSame($expected, Helpers::getLink('/my/relative/path'));
        $this->assertSame($expected, Helpers::getLink('my/relative/path'));
    }
}
