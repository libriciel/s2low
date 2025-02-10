<?php

use Monolog\Level;
use S2lowLegacy\Class\actes\ActesAnalyseFichierRecuController;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActesUpdateClassificationSQL;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\LogsSQL;

class ActesAnalyseFichierRecuControllerTest extends S2lowTestCase
{
    private const TEST_ARCHIVE_MISILCL_PATH = __DIR__ . "/../fixtures/test-archive-MISILCL";

    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;
    private $tmp_dir2;
    private $actes_files_upload_root;
    private $actes_response_tmp_local_path;
    private $actes_response_error_path;

    private $actes_ministere_acronyme;

    public function tearDown(): void
    {
        parent::tearDown();

        $this->tmpFolder->delete($this->actes_response_tmp_local_path);
        $this->tmpFolder->delete($this->actes_response_error_path);
        $this->tmpFolder->delete($this->tmp_dir);
        $this->tmpFolder->delete($this->tmp_dir2);
        $this->tmpFolder->delete($this->actes_files_upload_root);
    }

    /**
     * @throws Exception
     */
    public function testAnalyseAllEmpty()
    {
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        self::assertTrue(
            $this->testHandler->hasRecord(
                'Traitement de 0 répertoire trouvés',
                Level::Info
            )
        );
    }

    private function createActesAnalyseFichierRecuController(
        string $actesResponseTmpLocalPath = null,
        string $actesResponseErrorPath = null
    ): ActesAnalyseFichierRecuController {
        $actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);
        $actesScriptHelper = self::getContainer()->get(ActesScriptHelper::class);
        $actesUpdateClassificationSQL = self::getContainer()->get(ActesUpdateClassificationSQL::class);
        $actesEnvelopeSQL = self::getContainer()->get(ActesEnvelopeSQL::class);
        $actesIncludedFileSQL = self::getContainer()->get(ActesIncludedFileSQL::class);

        $actes_response_tmp_local_path = $actesResponseTmpLocalPath ?? $this->actes_response_tmp_local_path;
        $actes_response_error_path = $actesResponseErrorPath ?? $this->actes_response_error_path;
        $actes_files_upload_root = $actes_files_upload_root ?? $this->actes_files_upload_root;
        $actes_ministere_acronyme = self::getContainer()->getParameter('app.actes_ministere_acronyme');


        return new ActesAnalyseFichierRecuController(
            $this->s2lowLogger,
            $actes_response_tmp_local_path,
            $actes_response_error_path,
            $actesTransactionsSQL,
            $actesScriptHelper,
            $actesUpdateClassificationSQL,
            $actesEnvelopeSQL,
            $actesIncludedFileSQL,
            $actes_files_upload_root,
            $actes_ministere_acronyme,
        );
    }

    /**
     * @throws Exception
     */
    public function testAnalyseAllBadDirectory()
    {
        $bad_dir = $this->tmp_dir . "/test_bad";
        mkdir($bad_dir);

        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController(
            actesResponseTmpLocalPath: $this->tmp_dir,
            actesResponseErrorPath: $this->tmp_dir2
        );
        $actesAnalyseFichierRecuController->analyseAll();

        $this->assertFileExists($this->tmp_dir2 . "/test_bad");

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Echec du traitement de $bad_dir : Aucun fichier de type enveloppe métier n'a été trouvé dans le répertoire $bad_dir",
                Level::Error
            )
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Déplacement du répertoire test_bad vers $this->tmp_dir2",
                Level::Error
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testAnalyseAll()
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);

        $actesTransactionsSQL = $this->mockGetBySirenAndNumeroInterne($transaction_id);

        $this->copyDirectoryToAnalysePath(self::TEST_ARCHIVE_MISILCL_PATH);

        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, $transaction_info['status_id']);

        $this->assertMatchesRegularExpression(
            "#Reçu par le {$this->actes_ministere_acronyme} le#",
            $transaction_info['message']
        );

        $logsSQL = self::getContainer()->get(LogsSQL::class);
        $liste = $logsSQL->getLastLog();

        $this->assertMatchesRegularExpression(
            "#Transaction.*[0-9]* : passage à l'état acquittement reçu#",
            $liste['message']
        );

        $this->assertEquals(array('.', '..'), scandir("{$this->tmp_dir}"));
    }

    private function createTransaction($status)
    {
        $envelope_id = self::getContainer()->get(ActesEnvelopeSQL::class)->create(
            1,
            "000000000/abc-EACT--210703385--20170612-2.tar.gz"
        );

        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,antivirus_check,number,type) VALUES (?,?,?,?,?,?,?) returning ID;";
        return $this->getSQLQuery()->queryOne($sql, $envelope_id, $status, 1, 1, true, '20170721D', 1);
    }

    private function mockGetBySirenAndNumeroInterne($transaction_id)
    {
        $actesTransactionsSQL = $this->getMockBuilder(ActesTransactionsSQL::class)
            ->setConstructorArgs(array($this->getSQLQuery(), self::getContainer()->get(Database::class)))
            ->setMethods(array('getBySirenAndNumeroInterne'))
            ->getMock();
        $actesTransactionsSQL->method('getBySirenAndNumeroInterne')->willReturn($transaction_id);
        $this->getObjectInstancier()->set(ActesTransactionsSQL::class, $actesTransactionsSQL);
        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        return $actesTransactionsSQL;
    }

    private function copyDirectoryToAnalysePath($directory)
    {
        exec("cp -r $directory {$this->actes_response_tmp_local_path}/test");
    }

    /**
     * @throws Exception
     */
    public function testAnalyseAllActeNotFound()
    {
        $this->copyDirectoryToAnalysePath(self::TEST_ARCHIVE_MISILCL_PATH);
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                'Aucune transation trouver pour le couple SIREN 000000000 - numéro interne 20170721D',
                Level::Error
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testAnalyseAllActeErrorRep()
    {
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController(
            actesResponseTmpLocalPath: $this->actes_response_tmp_local_path . "/not-exists/",
            actesResponseErrorPath: $this->actes_response_error_path
        );

        try {
            $actesAnalyseFichierRecuController->analyseAll();
        } catch (Exception $e) {
            $this->assertEquals(
                "Erreur lors de la lecture du répertoire  {$this->actes_response_tmp_local_path}/not-exists/",
                $e->getMessage()
            );
        }

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Erreur lors de la lecture du répertoire  {$this->actes_response_tmp_local_path}/not-exists/",
                Level::Error
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testEnveloppeAnomalie()
    {
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-anomalie");
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['last_status_id']);

        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['status_id']);

        $this->assertMatchesRegularExpression(
            "#Enveloppe rejetée par le {$this->actes_ministere_acronyme}#",
            $transaction_info['message']
        );
        $this->assertEquals(array('.', '..'), scandir("{$this->tmp_dir}"));
    }

    /**
     * @throws Exception
     */
    public function testEnveloppeAnomalieAvecErreur()//La DGCL renvoie des enveloppes de ce type...
    {
        // Elles doivent donc passer même s'il faut modifier le XSD
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-message-anomalie-avec-erreur");
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseOneFileMoveIfError("test");

        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Anomalie trouvé pour l'acte : 20170721D",
                Level::Info
            )
        );
    }

    public function testEnveloppeAnomalieAvecErreurMalPresentee()   //La DGCL renvoie des enveloppes de ce type...
    {
        // Elles doivent donc passer même s'il faut modifier le XSD
        $this->copyDirectoryToAnalysePath($this->projectDir . "/test/PHPUnit/class/fixtures/test-message-anomalie-avec-erreur-mal-presentee");
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();

        $actesAnalyseFichierRecuController->analyseOneFileMoveIfError("test");

        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                "ERREUR2' is not a valid value of the local atomic type",
                Level::Error
            )
        );

        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Déplacement du répertoire test",
                Level::Error
            )
        );

        $this->assertEquals(array('.', '..'), scandir("{$this->actes_response_tmp_local_path}"));
        $this->assertTrue(in_array("test", scandir("{$this->actes_response_error_path}")));
    }

    /**
     * @throws Exception
     */
    public function testMessageMetierAnomalie()
    {
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-message-anomalie");
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['last_status_id']);

        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['status_id']);
        $this->assertMatchesRegularExpression(
            "#Anomalie signalee par le MI : 042 - Ca ne fonctionne pas#",
            $transaction_info['message']
        );
        $this->assertEquals(array('.', '..'), scandir("{$this->tmp_dir}"));
    }

    /**
     * @throws Exception
     */
    public function testMessageMetierAnomalieAfterAcquitter()
    {
        $this->copyDirectoryToAnalysePath(self::TEST_ARCHIVE_MISILCL_PATH);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();


        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-message-anomalie");
        $actesAnalyseFichierRecuController->analyseAll();

        $actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, $transaction_info['last_status_id']);
    }

    /**
     * @throws Exception
     */
    public function testCourrierSimple()
    {
        $transaction_id_orig = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id_orig);
        mkdir($this->actes_files_upload_root . "/000000000/20170725A/", 0777, true);
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-courrier-simple");
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = self::getContainer()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1, $enveloppe_info['user_id']);

        $actesTransactionSQL = self::getContainer()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesTransactionSQL->getIdByEnvelopeId($enveloppe_info['id']);

        $info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertNotEmpty($actesTransactionSQL->getRelatedTransaction($transaction_id_orig));


        $this->assertEquals("2017-07-25", mb_substr($info['decision_date'], 0, 10));

        $this->cleanAnalysePath();
    }

    private function cleanAnalysePath(): void
    {
        array_map('unlink', glob($this->actes_files_upload_root . "/000000000/20170725A/*"));
        rmdir($this->actes_files_upload_root . "/000000000/20170725A/");
        rmdir($this->actes_files_upload_root . "/000000000/");
    }

    /**
     * [Correction bug]
     * Il faut que le file_path contienne bien le chemin relatif de l'enveloppe ( pas de / au début)
     * Sinon la correspondance fichier sur disque / id de l'enveloppe posera problème
     * @throws Exception
     */
    public function testFile_pathEnveloppeCourrierSimple()
    {
        $transaction_id_orig = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id_orig);
        mkdir($this->actes_files_upload_root . "/000000000/20170725A/", 0777, true);
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-courrier-simple");
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        var_dump($enveloppe_info['file_path']);
        $this->assertEquals(
            '000000000/20170725A/abc-TACT--SPREF0011-000000000-20170725-1.tar.gz',
            $enveloppe_info['file_path']
        );

        $this->cleanAnalysePath();
    }

    /**
     * Après 15J, le repertoire risque d'être détruit...
     * @throws Exception
     */
    public function testCourrierSimpleApres15J()
    {
        $transaction_id_orig = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id_orig);

        $this->copyDirectoryToAnalysePath($this->projectDir . "/test/PHPUnit/class/fixtures/test-courrier-simple");
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = self::getContainer()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1, $enveloppe_info['user_id']);

        $actesTransactionSQL = self::getContainer()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesTransactionSQL->getIdByEnvelopeId($enveloppe_info['id']);

        $info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertNotEmpty($actesTransactionSQL->getRelatedTransaction($transaction_id_orig));


        $this->assertEquals("2017-07-25", mb_substr($info['decision_date'], 0, 10));
        $this->cleanAnalysePath();
    }

    /**
     * Si on a un fichier à la place du répertoire correspondant à l'acte, on ne veut pas qu'il soit détruit mais
     * qu'une exception soit lancée.
     * @throws Exception
     */
    public function testCourrierSimpleApres15JFichierPenible()
    {
        mkdir($this->actes_files_upload_root . "/000000000/", 0777, true);
        file_put_contents($this->actes_files_upload_root . "/000000000/20170725A", "pouet", true);
        $transaction_id_orig = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id_orig);
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-courrier-simple");

        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                "un fichier de ce nom existe déjà",
                Level::Error
            )
        );

        unlink($this->actes_files_upload_root . "/000000000/20170725A");
        rmdir($this->actes_files_upload_root . "/000000000/");
    }

    /**
     * @throws Exception
     */
    public function testDefereTA()
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id);
        mkdir($this->actes_files_upload_root . "/000000000/20170725A/", 0777, true);
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-defere-ta");
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = self::getContainer()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1, $enveloppe_info['user_id']);

        $actesTransactionSQL = self::getContainer()->get(ActesTransactionsSQL::class);
        $this->assertNotEmpty($actesTransactionSQL->getRelatedTransaction($transaction_id));
    }

    /**
     * @throws Exception
     */
    public function testCourrierSimpleRenumerote()
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id);

        mkdir($this->actes_files_upload_root . "/000000000/20170725A/", 0777, true);
        $this->copyDirectoryToAnalysePath($this->projectDir . "/test/PHPUnit/class/fixtures/test-courrier-simple-renumerote");

        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = self::getContainer()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1, $enveloppe_info['user_id']);

        $actesTransactionSQL = self::getContainer()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesTransactionSQL->getIdByEnvelopeId($enveloppe_info['id']);

        $info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertEquals("2017-07-25", mb_substr($info['decision_date'], 0, 10));

        $actesRetriever = new ActesRetriever(
            $this->actes_files_upload_root,
            self::getContainer()->get(OpenStackSwiftWrapper::class),
            $this->s2lowLogger,
        );

        $path = $actesRetriever->getPath($enveloppe_info['file_path']);


        $pharData = new PharData($path);
        $all_files_in_tar_gz = [];

        /** @var PharFileInfo $file */
        foreach ($pharData as $file) {
            $all_files_in_tar_gz[] = $file->getBasename();
        }
        sort($all_files_in_tar_gz);

        $this->assertEquals(
            [
                '034-000000000-20170701-20170725A-AI-2-1_16.pdf',
                '034-000000000-20170701-20170725A-AI-2-1_17.xml',
                'TACT--SPREF0011-000000000-20170725-1.xml',
                'logo_s2low.jpg',
                'message_body.html'
            ],
            $all_files_in_tar_gz
        );
    }

    /**
     * @throws Exception
     */
    public function testMessageMultiCanal()
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id);
        mkdir($this->actes_files_upload_root . "/000000000/20170725A/", 0777, true);
        $this->copyDirectoryToAnalysePath(__DIR__ . "/fixtures/mail-suite-multicanal/");
        $actesAnalyseFichierRecuController = $this->createActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $this->assertEquals(array('.', '..'), scandir("{$this->tmp_dir}"));

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Message de réponse à un multicanal",
                Level::Info
            )
        );
        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Suppression du répertoire",
                Level::Info
            )
        );

        $transaction_info = self::getContainer()->get(ActesTransactionsSQL::class)->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS, $transaction_info['last_status_id']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->tmp_dir = $this->tmpFolder->create();
        $this->tmp_dir2 = $this->tmpFolder->create();

        $this->actes_files_upload_root = $this->tmpFolder->create();
        $this->actes_response_tmp_local_path = $this->tmpFolder->create();
        $this->actes_response_error_path = $this->tmpFolder->create();

        $this->actes_ministere_acronyme = self::getContainer()->getParameter('app.actes_ministere_acronyme');
    }
}
