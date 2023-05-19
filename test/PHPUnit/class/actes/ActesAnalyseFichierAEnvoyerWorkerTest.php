<?php

use S2low\Services\PdfValidator;
use S2lowLegacy\Class\actes\ActesAnalyseFichierAEnvoyerWorker;
use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActesTypePJSQL;
use S2lowLegacy\Class\actes\ActesUpdateClassificationSQL;
use S2lowLegacy\Class\actes\ArchiveValidatorFactory;
use S2lowLegacy\Class\PadesValid;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Model\LogsSQL;

require_once __DIR__ . "/ActesCreator.php";

class ActesAnalyseFichierAEnvoyerWorkerTest extends S2lowTestCase
{
    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->tmp_dir = $this->tmpFolder->create();

        $padesValid = $this->getMockBuilder(PadesValid::class)->disableOriginalConstructor()->getMock();
        $padesValid->method("validate")->willReturn(true);
        $this->getObjectInstancier()->set(PadesValid::class, $padesValid);
    }

    private function getActesAnalysFichierAEnvoyerWorker()
    {
        return $this->getObjectInstancier()->get(ActesAnalyseFichierAEnvoyerWorker::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tmpFolder->delete($this->tmp_dir);
    }

    public function testQueueName()
    {
        $this->assertEquals(
            ActesAnalyseFichierAEnvoyerWorker::QUEUE_NAME,
            $this->getActesAnalysFichierAEnvoyerWorker()->getQueueName()
        );
    }

    public function testGetId()
    {
        $this->assertEquals(
            42,
            $this->getActesAnalysFichierAEnvoyerWorker()->getData(42)
        );
    }

    /**
     * @throws Exception
     */
    public function testGetList()
    {
        $data = $this->createOneTransaction(__DIR__ . "/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");
        $result = $this->getActesAnalysFichierAEnvoyerWorker()->getAllId();
        $this->assertEquals([$data['envelope_id']], $result);
    }

    /**
     * @throws Exception
     */
    public function testValidateAllOne()
    {
        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, $transaction_info['status_id']);
        $this->assertEquals(
            "Accepté par le TdT : validation OK",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $liste = $logsSQL->getLastLog();
        $this->assertMatchesRegularExpression("#Transaction.*[0-9]* : passage à l'état en attente#", $liste['message']);
    }

    /**
     * @throws Exception
     */
    public function testValidateAllOneWithTypologieKO()
    {
        $this->getObjectInstancier()->set('actes_appli_trigramme', 'abc');
        $this->getObjectInstancier()->set('actes_appli_quadrigramme', 'TACT');

        $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class)->insertActeNature(4, 'CC', 'Contrat et convention');
        $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class)->insertActeTypePJ(4, '99_AU', 'test');

        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/ok/abc-TACT--000000000--20181024-4.tar.gz");

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['status_id']);
        $this->assertEquals(
            "Enveloppe invalide : La typologie 10_DE n'est pas permise sur le fichier 10_DE-002-000000000-20181001-201810241655-CC-1-1_1.pdf",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $liste = $logsSQL->getLastLog();
        $this->assertMatchesRegularExpression("#Transaction.*[0-9]* : passage à l'état erreur#", $liste['message']);
    }


    /**
     * @throws Exception
     */
    public function testValidateAllOneWithTyplogieOK()
    {

        $this->getObjectInstancier()->set('actes_appli_trigramme', 'abc');
        $this->getObjectInstancier()->set('actes_appli_quadrigramme', 'TACT');

        $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class)->insertActeNature(4, 'CC', 'Contrat et convention');
        $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class)->insertActeTypePJ(4, '99_CO', 'test');
        $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class)->insertActeTypePJ(4, '10_DE', 'test');

        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/ok/abc-TACT--000000000--20181024-4.tar.gz");
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, $transaction_info['status_id']);
        $this->assertEquals(
            "Accepté par le TdT : validation OK",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $liste = $logsSQL->getLastLog();
        $this->assertMatchesRegularExpression("#Transaction.*[0-9]* : passage à l'état en attente#", $liste['message']);
    }


    /**
     * @throws Exception
     */
    public function testValidateAllOneBad()
    {
        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/bad/SLO-EACT--214502494--20170717-5.tar.gz");
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['status_id']);
        $this->assertEquals(
            "Enveloppe invalide : Le format text/plain du fichier 045-214502494-20170717-D201717-DE-1-1_1.txt n'est pas autorisé",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $liste = $logsSQL->getLastLog();
        $this->assertMatchesRegularExpression("#Transaction.*[0-9]* : passage à l'état erreur#", $liste['message']);
    }

    /**
     * @param $archivepath
     * @return array|bool|mixed
     * @throws Exception
     */
    private function validateAll($archivepath, $is_marche_public = false)
    {
        $data = $this->createOneTransaction($archivepath, $is_marche_public);
        $this->getActesAnalysFichierAEnvoyerWorker()->work($data['envelope_id']);
        return $data['transaction_id'];
    }

    /**
     * @throws Exception
     */
    public function testValidateEmptyFile()
    {
        $data = $this->createOneTransaction(null);

        try {
            $this->getActesAnalysFichierAEnvoyerWorker()->work($data['envelope_id']);
        } catch (Exception $e) {
            $this->assertEquals(get_class($e), RecoverableException::class);
        }

        $transaction_id = $data["transaction_id"];
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_POSTE, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_POSTE, $transaction_info['status_id']);
    }

    /**
     * @param $archivepath
     * @param bool $is_marche_public
     * @return array
     * @throws Exception
     */
    private function createOneTransaction($archivepath, $is_marche_public = false)
    {
        $actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);
        $transaction_id = $actesCreator->createTransaction(
            ActesStatusSQL::STATUS_POSTE,
            $archivepath,
            $this->tmp_dir
        );

        $envelope_id = $actesCreator->getLastEnvelopeId();
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $actesTransactionsSQL->setAntivirusCheck($transaction_id);

        if ($is_marche_public) {
            $sql = "UPDATE actes_transactions SET nature_code=?,classification=? WHERE id=?";
            $this->getSQLQuery()->query($sql, "4", "1.1", $transaction_id);
        }

        return ['envelope_id' => $envelope_id, 'transaction_id' => $transaction_id];
    }

    /**
     * @throws Exception
     */
    public function testValidateAllOnePadesFailedRecoverable()
    {

        $padesValid = $this->getMockBuilder(PadesValid::class)->disableOriginalConstructor()->getMock();
        $padesValid->method("validate")->willThrowException(new RecoverableException("erreur de test"));
        $this->getObjectInstancier()->set(PadesValid::class, $padesValid);

        $this->setExpectedException(RecoverableException::class, "erreur de test");
        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_POSTE, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_POSTE, $transaction_info['status_id']);
    }

    /**
     * @throws Exception
     */
    public function testValidateAllOnePadesFailedNotRecoverable()
    {

        $padesValid = $this->getMockBuilder(PadesValid::class)->disableOriginalConstructor()->getMock();
        $padesValid->method("validate")->willThrowException(new Exception("erreur de test"));
        $this->getObjectInstancier()->set(PadesValid::class, $padesValid);

        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['status_id']);
        $this->assertEquals(
            "Enveloppe invalide : Problème sur 99_DE-045-214502494-20170717-D201717-DE-1-1_1.pdf : erreur de test",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $liste = $logsSQL->getLastLog();
        $this->assertMatchesRegularExpression("#Transaction.*[0-9]* : passage à l'état erreur#", $liste['message']);
    }

    /**
     * @throws Exception
     */
    public function testValidateAllOneNoChekingCertificate()
    {
        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/ok/SLO-EACT--214502494--20170717-6.tar.gz", true);
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, $transaction_info['status_id']);
        $this->assertEquals(
            "Accepté par le TdT : validation OK",
            $transaction_info['message']
        );

        $liste = $logsSQL->getLastLog();
        $this->assertMatchesRegularExpression("#Transaction.*[0-9]* : passage à l'état en attente#", $liste['message']);
    }

    /**
     * @throws Exception
     */
    public function testValidateAllNoChekingCertificateGlobale()
    {
        $this->getObjectInstancier()->set("actes_dont_valid_signing_certificate", true);
        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, $transaction_info['last_status_id']);
    }


    /**
     * @throws Exception
     */
    public function testValidateAllOneWithTypologieKOTypologieChecked()
    {

        $actesUpdateClassificationSQL = $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class);

        $actesUpdateClassificationSQL->updateClassification(
            "123456789",
            file_get_contents(__DIR__ . "/../../class/actes/fixtures/classification-exemple.xml")
        );

        $this->getObjectInstancier()->set('actes_appli_trigramme', 'abc');
        $this->getObjectInstancier()->set('actes_appli_quadrigramme', 'TACT');

        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/bad/abc-TACT--000000000--20181024-4.tar.gz");

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['status_id']);
        $this->assertEquals(
            "Enveloppe invalide : La typologie 99_AU n'est pas permise sur le fichier 99_AU-002-000000000-20181001-201810241655-CC-1-1_2.pdf pour la nature 4",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get(LogsSQL::class);
        $liste = $logsSQL->getLastLog();
        $this->assertMatchesRegularExpression("#Transaction.*[0-9]* : passage à l'état erreur#", $liste['message']);
    }

    /**
     * @throws Exception
     */
    public function testValidateAllOneWithTypologieOKTypologieNotChecked()
    {
        $actesUpdateClassificationSQL = $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class);

        $actesUpdateClassificationSQL->updateClassification(
            "123456789",
            file_get_contents(__DIR__ . "/../../class/actes/fixtures/classification-exemple.xml")
        );

        $this->getObjectInstancier()->set('actes_appli_trigramme', 'abc');
        $this->getObjectInstancier()->set('actes_appli_quadrigramme', 'TACT');

        $transaction_id = $this->validateAll(__DIR__ . "/../../fixtures/bad/abc-TACT--000000000--20181024-5.tar.gz");

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION, $transaction_info['last_status_id']);
    }

    public function testErrorIsHandled()
    {
        $archiveValidator = $this->getMockBuilder(\Libriciel\LibActes\ArchiveValidator::class)->disableOriginalConstructor()->getMock();
        $archiveValidator->method('validate')->willThrowException(new Error('oupsie'));

        $archiveValidatorFactory = $this->getMockBuilder(ArchiveValidatorFactory::class)
            ->disableOriginalConstructor()->getMock();

        $archiveValidatorFactory->method('get')->willReturn($archiveValidator);

        $worker = new ActesAnalyseFichierAEnvoyerWorker(
            $this->getObjectInstancier()->get(S2lowLogger::class),
            $this->getObjectInstancier()->get(ActesTransactionsSQL::class),
            $this->getObjectInstancier()->get('actes_appli_trigramme'),
            $this->getObjectInstancier()->get('actes_appli_quadrigramme'),
            $this->getObjectInstancier()->get(ActesScriptHelper::class),
            $this->getObjectInstancier()->get(PadesValid::class),
            $this->getObjectInstancier()->get(WorkerScript::class),
            $this->getObjectInstancier()->get('actes_dont_valid_signing_certificate'),
            $this->getObjectInstancier()->get(ActesTypePJSQL::class),
            $this->getObjectInstancier()->get(PdfValidator::class),
            $archiveValidatorFactory
        );

        $data = $this->createOneTransaction(__DIR__ . "/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");

        $worker->work($data['envelope_id']);

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($data['transaction_id']);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $transaction_info['last_status_id']);
        $statusInfo = $actesTransactionsSQL->getLastTransactionWorkflowInfo($data['transaction_id']);
        $this->assertEquals("Enveloppe invalide : oupsie", $statusInfo['message']);
    }
}
