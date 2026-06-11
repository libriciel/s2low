<?php

namespace PHPUnit\helios;

use HeliosDirectoriesManager;
use IntegrationTests\S2lowIntegrationTestCase;
use Psr\Log\LoggerInterface;
use S2low\Enum\UserRole;
use S2low\Infrastructure\Directory;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\helios\IncomingFileProcessor;
use S2lowLegacy\Class\helios\HeliosFilesFactory;
use S2lowLegacy\Class\helios\HeliosPurge;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use SplFileObject;

class HeliosPurgeTest extends S2lowIntegrationTestCase
{
    private HeliosDirectoriesManager $heliosDirectoriesManager;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->setUserWithRole(UserRole::Utilisateur);

        $this->tmpDirectory = sys_get_temp_dir() . '/' . uniqid('phpunit');
        mkdir($this->tmpDirectory);

        $this->heliosDirectoriesManager = new HeliosDirectoriesManager();
        $this->heliosDirectoriesManager->createDirectories();

        self::getContainer()->set(
            'app.heliosFilesIncoming',
            new Directory(
                $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path,
            )
        );

        self::getContainer()->set(
            'app.heliosFilesUpload',
            new Directory(
                $this->heliosDirectoriesManager->helios_files_upload_root
            )
        );

        self::getContainer()->set(
            'app.localFileResolver.pes_aller',
            new LocalFileResolver(
                self::getContainer()->get(HeliosTransactionsSQL::class),
                $this->heliosDirectoriesManager->helios_files_upload_root,
            )
        );


        self::getContainer()->set(
            'app.heliosResponseDirectory',
            new Directory(
                $this->heliosDirectoriesManager->helios_response_root
            )
        );

        self::getContainer()->set(
            'app.heliosErrorDirectory',
            new Directory(
                $this->heliosDirectoriesManager->helios_responses_error_path
            )
        );

        self::getContainer()->set(
            'app.heliosOcreDirectory',
            new Directory(
                $this->heliosDirectoriesManager->helios_ocre
            )
        );

        $this->heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $this->heliosController = self::getContainer()->get(HeliosController::class);
        $this->heliosFilesFactory = self::getContainer()->get(HeliosFilesFactory::class);
        $this->incomingFileProcessor = self::getContainer()->get(IncomingFileProcessor::class);
        $this->heliosPurge = self::getContainer()->get(HeliosPurge::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->heliosDirectoriesManager->clearDirectories();
        foreach (
            [$this->tmpDirectory] as $dirname
        ) {
            array_map('unlink', glob("$dirname/*.*"));
            rmdir($dirname);
        }
    }

    public function testPesAcquit()
    {

        $tmp_file = $this->tmpDirectory . "/pes_aller.xml";
        file_put_contents($tmp_file, file_get_contents(__DIR__ . "/fixtures/pes_aller.xml"));

        $_FILES['enveloppe'] = array(
            'name' => 'pes_aller.xml',
            'tmp_name' => $tmp_file,
            'size' => filesize($tmp_file),
            'error' => UPLOAD_ERR_OK
        );

        $pesAllerRetriever = new PesAllerRetriever(
            $this->heliosDirectoriesManager->helios_files_upload_root,
            self::getContainer()->get(OpenStackSwiftWrapper::class),
            self::getContainer()->get(LoggerInterface::class)
        );

        self::getContainer()->set(PesAllerRetriever::class, $pesAllerRetriever);

        $id_t = $this->heliosController->import(13);

        // Les informations sont ajoutées par le cron IncomingFileProcessor
        $info_from_pes_aller['nom_fic'] = "03f432a4f6d35110bf309fb525eb61f7";
        $info_from_pes_aller['cod_col'] = "400";
        $info_from_pes_aller['cod_bud'] = "01";
        $info_from_pes_aller['id_post'] = "034000";
        $this->heliosTransactionSQL->setInfoFromPESAller($id_t, $info_from_pes_aller);

        $filename = "pes_acquit_modif.xml";
        $path = $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename";
        file_put_contents(
            $path,
            file_get_contents(__DIR__ . "/fixtures/pes_acquit_modif.xml")
        );

        $file = new SplFileObject($path);

        $this->incomingFileProcessor->process($file);

        $heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $this->assertEquals(
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
            $heliosTransactionSQL->getLatestStatusId($id_t)
        );

        $heliosFilesNames = $this->heliosFilesFactory->get($id_t);

        $this->assertTrue(file_exists($heliosFilesNames->getPesAquitFilename()));       // LE PES ACQUIT EST CREE !!
        $this->assertTrue(is_file($heliosFilesNames->getPesAquitFilename()));           // ET C'EST UN FICHIER !!
        $this->assertTrue(file_exists($heliosFilesNames->getPesAllerFilename()));      // LE PES ACQUIT EST CREE !!
        $this->assertTrue(is_file($heliosFilesNames->getPesAllerFilename()));           // ET C'EST UN FICHIER ITOU.
        $this->assertTrue(file_exists($heliosFilesNames->getPesAquitCompletename()));   // Apparemment normal,
        $this->assertFalse(is_file($heliosFilesNames->getPesAquitCompletename()));      // on a juste un répertoire

        $this->heliosPurge->purge($id_t);

        $this->assertFalse(file_exists($heliosFilesNames->getPesAquitFilename()));       // LE PES ACQUIT EST SUPPRIME !!
        $this->assertFalse(file_exists($heliosFilesNames->getPesAllerFilename()));      // LE PES ACQUIT EST SUPPRIME !!
        $this->assertTrue(file_exists($heliosFilesNames->getPesAquitCompletename()));   // CA N'A PAS BOUGE ...
        $this->assertFalse(is_file($heliosFilesNames->getPesAquitCompletename()));      // on a juste un répertoire
    }
}
