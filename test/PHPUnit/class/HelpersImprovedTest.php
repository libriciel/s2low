<?php

use S2lowLegacy\Class\Helpers;

class HelpersImprovedTest extends S2lowTestCase
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

    // ==========================================
    // Méthodes d'entrée de requête / Superglobales
    // ==========================================


    // ==========================================
    // Méthodes de Session
    // ==========================================

    public function testSessionLifecycle()
    {
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->putInSession('key1', 'value1');
        $this->assertSame('value1', $_SESSION['temp']['key1']);

        // Test de récupération avec delete=false
        $this->assertSame('value1', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->getFromSession('key1', false));
        $this->assertSame('value1', $_SESSION['temp']['key1']);

        // Test de récupération avec delete=true (par défaut)
        $this->assertSame('value1', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->getFromSession('key1'));
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->getFromSession('key1'));
        $this->assertFalse(isset($_SESSION['temp']['key1']));

        // Test de purge
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->putInSession('key2', 'value2');
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->purgeTempSession();
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\SessionHelper::class)->getFromSession('key2'));
    }

    // ==========================================
    // Méthodes de Redirection / Sortie
    // ==========================================


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
        $this->assertSame('14 septembre 2015', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->TimestampToString($timestamp));
    }

    public function testGetFromBDD()
    {
        $this->assertSame('any_db_value', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->getFromBDD('any_db_value'));
    }

    public function testEscapeForXML()
    {
        // Nous vérifions le comportement réel du code (échappement de " en \")
        // bien que cela soit techniquement incorrect pour du XML standard.
        $this->assertSame('Hello \\"World\\"', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->escapeForXML('Hello "World"'));
        $this->assertSame('', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->escapeForXML(null));
    }

    public function testGetFromXMLElt()
    {
        $elt = new SimpleXMLElement('<node>valeur_é</node>');
        $this->assertSame('valeur_é', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->getFromXMLElt($elt));
    }

    public function testTruncateString()
    {
        $str = 'Un texte assez long pour dépasser la limite';
        $this->assertSame('Un texte...', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->truncateString($str, 8, true));
        $this->assertSame('Un texte', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->truncateString($str, 8, false));

        // Chaîne plus courte que la longueur max
        $this->assertSame('Court', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\FormatHelper::class)->truncateString('Court', 10, true));
    }

    public function testGetPrettyHours()
    {
        $this->assertSame('14h 05min 32s', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getPrettyHours('14:05:32'));
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getPrettyHours('invalid'));
    }

    public function testGetTimestampFromBDDDate()
    {
        $dateStr = '2015-09-14 07:22:42';
        $expected = strtotime($dateStr . ' Europe/Paris');
        $this->assertSame($expected, \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getTimestampFromBDDDate($dateStr));
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getTimestampFromBDDDate('invalid-date'));
    }

    public function testGetDateFromBDDDate()
    {
        $dateStr = '2015-09-14 07:22:42';
        // Avec hours = false
        $this->assertSame('14 septembre 2015', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getDateFromBDDDate($dateStr, false));

        // Avec hours = true
        $this->assertSame('14 septembre 2015 à 07h22min42s', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getDateFromBDDDate($dateStr, true));

        // Invalide
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getDateFromBDDDate('invalid-date'));
    }

    public function testGetANSIDateFromBDDDate()
    {
        $dateStr = '2015-09-14 07:22:42';
        $this->assertSame('2015-09-14', \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getANSIDateFromBDDDate($dateStr));
        $this->assertNull(\S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\DateHelper::class)->getANSIDateFromBDDDate('invalid'));
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
