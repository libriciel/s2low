<?php

namespace PHPUnit\helios;

use HeliosDirectoriesManager;
use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecu;
use S2lowLegacy\Class\helios\HeliosFilesFactory;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosFileNamesTest extends S2lowIntegrationTestCase
{
    private HeliosDirectoriesManager $heliosDirectoriesManager;

    private HeliosAnalyseFichierRecu $heliosAnalyseFichierRecu;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $this->heliosDirectoriesManager = new HeliosDirectoriesManager();
        $this->heliosDirectoriesManager->createDirectories();

        $this->setUserWithRole(UserRole::Utilisateur);

        $this->tmpDirectory = sys_get_temp_dir() . "/" . uniqid("phpunit");
        mkdir($this->tmpDirectory);

        $this->heliosFilesFactory = new HeliosFilesFactory(
            $this->heliosTransactionSQL,
            $this->heliosDirectoriesManager->helios_files_upload_root,
            $this->heliosDirectoriesManager->helios_response_root,
        );

        $this->localFileResolver = new LocalFileResolver(
            self::getContainer()->get(HeliosTransactionsSQL::class),
            $this->heliosDirectoriesManager->helios_files_upload_root
        );

        $this->heliosController = new HeliosController(
            $this->localFileResolver,
            self::getContainer()->get('app.store.file.pes_aller'),
            self::getContainer()->get(ObjectInstancier::class),
            self::getContainer()->get(UserContext::class)
        );
        $this->heliosAnalyseFichierRecu = self::getContainer()->get(HeliosAnalyseFichierRecu::class);
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
        $id_transaction = $this->heliosController->import(13);
        // Les informations sont ajoutées par le cron HeliosAnalyseFichierRecu
        $info_from_pes_aller['nom_fic'] = "03f432a4f6d35110bf309fb525eb61f7";
        $info_from_pes_aller['cod_col'] = "400";
        $info_from_pes_aller['cod_bud'] = "01";
        $info_from_pes_aller['id_post'] = "034000";
        $this->heliosTransactionSQL->setInfoFromPESAller($id_transaction, $info_from_pes_aller);

        $filename = "pes_acquit_modif.xml";
        file_put_contents(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path . "/$filename",
            file_get_contents(__DIR__ . "/fixtures/pes_acquit_modif.xml")
        );

        $this->heliosAnalyseFichierRecu->analyseOneFileForWorker(
            $this->heliosDirectoriesManager->helios_ftp_response_tmp_local_path,
            $this->heliosDirectoriesManager->helios_response_root,
            $this->heliosDirectoriesManager->helios_responses_error_path,
            $this->heliosDirectoriesManager->helios_ocre,
            $filename
        );

        $heliosTransactionSQL = self::getContainer()->get(HeliosTransactionsSQL::class);
        $this->assertEquals(
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
            $heliosTransactionSQL->getLatestStatusId($id_transaction)
        );

        $heliosFilesNames = $this->heliosFilesFactory->get($id_transaction);

        $this->assertTrue(file_exists($heliosFilesNames->getPesAquitFilename()));
        $this->assertTrue(is_file($heliosFilesNames->getPesAquitFilename()));
        $this->assertTrue(file_exists($heliosFilesNames->getPesAllerFilename()));
        $this->assertTrue(is_file($heliosFilesNames->getPesAllerFilename()));
        $this->assertTrue(file_exists($heliosFilesNames->getPesAquitCompletename()));
        $this->assertFalse(is_file($heliosFilesNames->getPesAquitCompletename()));
    }
}
