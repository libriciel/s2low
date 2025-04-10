<?php

use S2lowLegacy\Class\actes\ActesAnalyseFichierRecuController;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActesUpdateClassificationSQL;
use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Class\ActesWorkspaceForTests;
use S2lowLegacy\Class\IActesWorkspace;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\LogsSQL;

class ActesAnalyseFichierRecuControllerTest extends S2lowTestCase
{
    private const TEST_ARCHIVE_MISILCL_PATH = __DIR__ . "/../fixtures/test-archive-MISILCL";

    private $actes_ministere_acronyme;
    private IActesWorkspace $workspace;
    private ActesRetriever $actesRetriever;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFolder = new TmpFolder();

        $this->actes_ministere_acronyme = ACTES_MINISTERE_ACRONYME;

        $this->workspace = new ActesWorkspaceForTests();

        $this->actesRetriever = new ActesRetriever(
            $this->getObjectInstancier()->get(OpenStackSwiftWrapper::class),
            $this->getObjectInstancier()->get(S2lowLogger::class),
            $this->workspace
        );
        $this->actesScriptHelper = new ActesScriptHelper(
            $this->getObjectInstancier()->get(ActesTransactionsSQL::class),
            $this->getObjectInstancier()->get(ActesEnvelopeSQL::class),
            $this->getObjectInstancier()->get('actes_appli_trigramme'),
            $this->actesRetriever
        );
    }

    /**
     * @throws Exception
     */
    public function testAnalyseAllEmpty()
    {
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();
        $logs = $this->getLogRecords();
        $this->assertEquals("Traitement de 0 répertoire trouvés", $logs[2][S2lowLogger::MESSAGE]);
    }

    /**
     * @throws Exception
     */
    public function testAnalyseAllBadDirectory()
    {
        $bad_dir = $this->workspace->getResponseTmpLocalPath() . '/test_bad';
        mkdir($bad_dir);

        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $this->assertFileExists($this->workspace->getResponseErrorPath() . '/test_bad');

        $logs = $this->getLogRecords();
        $this->assertEquals("Echec du traitement de $bad_dir : Aucun fichier de type enveloppe métier n'a été trouvé dans le répertoire $bad_dir", $logs[4][S2lowLogger::MESSAGE]);
        $this->assertEquals("Déplacement du répertoire test_bad vers {$this->workspace->getResponseErrorPath()}", $logs[5][S2lowLogger::MESSAGE]);
    }


    private function mockGetBySirenAndNumeroInterne($transaction_id)
    {
        $actesTransactionsSQL = $this->getMockBuilder(ActesTransactionsSQL::class)
            ->setConstructorArgs(array($this->getSQLQuery()))
            ->setMethods(array('getBySirenAndNumeroInterne'))
            ->getMock();
        $actesTransactionsSQL->method('getBySirenAndNumeroInterne')->willReturn($transaction_id);
        $this->getObjectInstancier()->set(ActesTransactionsSQL::class, $actesTransactionsSQL);
        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        return $actesTransactionsSQL;
    }

    /**
     * @throws Exception
     */
    public function testAnalyseAll()
    {

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);

        $actesTransactionsSQL =  $this->mockGetBySirenAndNumeroInterne($transaction_id);

        $this->copyDirectoryToAnalysePath(self::TEST_ARCHIVE_MISILCL_PATH);

        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, $transaction_info['status_id']);
        $this->assertMatchesRegularExpression(
            "#Reçu par le {$this->actes_ministere_acronyme} le#",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $liste = $logsSQL->getLastLog();
        $this->assertMatchesRegularExpression("#Transaction.*[0-9]* : passage à l'état acquittement reçu#", $liste['message']);

        $this->assertEquals(array('.','..'), scandir("{$this->workspace->getResponseTmpLocalPath()}"));
    }

    /**
     * @throws Exception
     */
    public function testAnalyseAllActeNotFound()
    {
        $this->copyDirectoryToAnalysePath(self::TEST_ARCHIVE_MISILCL_PATH);
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();
        $logs = $this->getLogRecords();
        $this->assertMatchesRegularExpression("#Aucune transation trouver pour le couple SIREN 000000000 - numéro interne 20170721D#", $logs[6][S2lowLogger::MESSAGE]);
    }

    private function createTransaction($status)
    {
        $envelope_id = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class)->create(
            1,
            "000000000/abc-EACT--210703385--20170612-2.tar.gz"
        );

        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,antivirus_check,number,type) VALUES (?,?,?,?,?,?,?) returning ID;";
        return $this->getSQLQuery()->queryOne($sql, $envelope_id, $status, 1, 1, true, '20170721D', 1);
    }


    /**
     * @throws Exception
     */
    public function testAnalyseAllActeErrorRep()
    {
        $this->workspace = new ActesWorkspace(
            '/not/a/dir/',
            '/not/a/dir/either/',
            '/not/a/dir/of/course/',
            '/not/a/dir/at/last/',
        );
        try {
            $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
            $actesAnalyseFichierRecuController->analyseAll();
        } catch (Exception $e) {
            $this->assertEquals("Erreur lors de la lecture du répertoire  /not/a/dir/either/", $e->getMessage());
        }
        $logs = $this->getLogRecords();
        $this->assertEquals("Erreur lors de la lecture du répertoire  /not/a/dir/either/", $logs[2]['message']);
    }

    /**
     * @throws Exception
     */
    public function testEnveloppeAnomalie()
    {
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-anomalie");
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['status_id']);
        $this->assertMatchesRegularExpression(
            "#Enveloppe rejetée par le {$this->actes_ministere_acronyme}#",
            $transaction_info['message']
        );
        $this->assertEquals(array('.','..'), scandir("{$this->workspace->getResponseTmpLocalPath()}"));
    }

    /**
     * @throws Exception
     */
    public function testEnveloppeAnomalieAvecErreur()   //La DGCL renvoie des enveloppes de ce type...
    {
                                                   // Elles doivent donc passer même s'il faut modifier le XSD
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-message-anomalie-avec-erreur");
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseOneFileMoveIfError("test");

        $logs = $this->getLogRecords();
        $this->assertMatchesRegularExpression(
            "#Anomalie trouvé pour l'acte : 20170721D#",
            $logs[2][S2lowLogger::MESSAGE]
        );
    }

    public function testEnveloppeAnomalieAvecErreurMalPresentee()   //La DGCL renvoie des enveloppes de ce type...
    {
                                                   // Elles doivent donc passer même s'il faut modifier le XSD
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-message-anomalie-avec-erreur-mal-presentee");
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseOneFileMoveIfError("test");

        $logs = $this->getLogRecords();
        $this->assertMatchesRegularExpression(
            "#ERREUR2' is not a valid value of the local atomic type#",
            $logs[2][S2lowLogger::MESSAGE]
        );
        $this->assertMatchesRegularExpression(
            "#Déplacement du répertoire test#",
            $logs[3][S2lowLogger::MESSAGE]
        );
        $this->assertEquals(array('.','..'), scandir("{$this->workspace->getResponseTmpLocalPath()}"));
        $this->assertTrue(in_array('test', scandir("{$this->workspace->getResponseErrorPath()}")));
    }
    /**
     * @throws Exception
     */
    public function testMessageMetierAnomalie()
    {
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-message-anomalie");
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['status_id']);
        $this->assertMatchesRegularExpression(
            "#Anomalie signalee par le MI : 042 - Ca ne fonctionne pas#",
            $transaction_info['message']
        );
        $this->assertEquals(array('.','..'), scandir("{$this->workspace->getResponseTmpLocalPath()}"));
    }


    /**
     * @throws Exception
     */
    public function testMessageMetierAnomalieAfterAcquitter()
    {
        $this->copyDirectoryToAnalysePath(self::TEST_ARCHIVE_MISILCL_PATH);


        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();


        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-message-anomalie");
        $actesAnalyseFichierRecuController->analyseAll();

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
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
        mkdir(
            $this->workspace->getFilesUploadRoot() . "/000000000/20170725A/",
            0777,
            true
        );
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-courrier-simple");
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1, $enveloppe_info['user_id']);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesTransactionSQL->getIdByEnvelopeId($enveloppe_info['id']);

        $info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertNotEmpty($actesTransactionSQL->getRelatedTransaction($transaction_id_orig));


        $this->assertEquals("2017-07-25", mb_substr($info['decision_date'], 0, 10));
    }

    /**
     * Après 15J, le repertoire risque d'être détruit...
     * @throws Exception
     */
    public function testCourrierSimpleApres15J()
    {
        $transaction_id_orig = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id_orig);
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-courrier-simple");
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1, $enveloppe_info['user_id']);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesTransactionSQL->getIdByEnvelopeId($enveloppe_info['id']);

        $info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertNotEmpty($actesTransactionSQL->getRelatedTransaction($transaction_id_orig));


        $this->assertEquals("2017-07-25", mb_substr($info['decision_date'], 0, 10));
    }

    /**
     * Si on a un fichier à la place du répertoire correspondant à l'acte, on ne veut pas qu'il soit détruit mais
     * qu'une exception soit lancée.
     * @throws Exception
     */
    public function testCourrierSimpleApres15JFichierPenible()
    {
        mkdir(
            $this->workspace->getFilesUploadRoot() . "/000000000/",
            0777,
            true
        );
        file_put_contents(
            $this->workspace->getFilesUploadRoot() . "/000000000/20170725A",
            "pouet",
            true
        );
        $transaction_id_orig = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id_orig);
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-courrier-simple");
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();

        $actesAnalyseFichierRecuController->analyseAll();

        $logs = $this->getLogRecords();
        $this->assertMatchesRegularExpression("#un fichier de ce nom existe déjà#", $logs[6][S2lowLogger::MESSAGE]);
    }

    /**
     * @throws Exception
     */
    public function testDefereTA()
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id);
        mkdir(
            $this->workspace->getFilesUploadRoot() . "/000000000/20170725A/",
            0777,
            true
        );
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-defere-ta");
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1, $enveloppe_info['user_id']);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $this->assertNotEmpty($actesTransactionSQL->getRelatedTransaction($transaction_id));
    }


    private function copyDirectoryToAnalysePath($directory)
    {
        exec("cp -r $directory {$this->workspace->getResponseTmpLocalPath()}/test");
    }

    /**
     * @throws Exception
     */
    public function testCourrierSimpleRenumerote()
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id);
        mkdir(
            $this->workspace->getFilesUploadRoot() . "/000000000/20170725A/",
            0777,
            true
        );
        $this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-courrier-simple-renumerote");
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1, $enveloppe_info['user_id']);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesTransactionSQL->getIdByEnvelopeId($enveloppe_info['id']);

        $info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertEquals("2017-07-25", mb_substr($info['decision_date'], 0, 10));

        $path = $this->actesRetriever->getPath($enveloppe_info['file_path']);


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
        mkdir(
            $this->workspace->getFilesUploadRoot() . "/000000000/20170725A/",
            0777,
            true
        );
        $this->copyDirectoryToAnalysePath(__DIR__ . "/fixtures/mail-suite-multicanal/");
        $actesAnalyseFichierRecuController = $this->getActesAnalyseFichierRecuController();
        $actesAnalyseFichierRecuController->analyseAll();
        $this->assertEquals(array('.','..'), scandir("{$this->workspace->getResponseTmpLocalPath()}"));
        $logs = $this->getLogRecords();
        $this->assertMatchesRegularExpression("#Message de réponse à un multicanal#", $logs[4][S2lowLogger::MESSAGE]);
        $this->assertMatchesRegularExpression("#Suppression du répertoire#", $logs[5]['message']);

        $actesTransactionsSQL =  $this->mockGetBySirenAndNumeroInterne($transaction_id);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS, $transaction_info['last_status_id']);
    }

    /**
     * @return mixed
     */
    private function getActesAnalyseFichierRecuController(): ActesAnalyseFichierRecuController
    {
        return new ActesAnalyseFichierRecuController(
            $this->getObjectInstancier()->get(S2lowLogger::class),
            $this->getObjectInstancier()->get(ActesTransactionsSQL::class),
            $this->actesScriptHelper,
            $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class),
            $this->getObjectInstancier()->get(ActesEnvelopeSQL::class),
            $this->getObjectInstancier()->get(ActesIncludedFileSQL::class),
            $this->actes_ministere_acronyme,
            $this->workspace
        );
    }
}
