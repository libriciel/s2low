<?php

require_once __DIR__."/ActesCreator.php";

class ActesAnalyseFichierAEnvoyerWorkerTest extends S2lowTestCase {

    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;

	/**
	 * @throws Exception
	 */
    protected function setUp(){
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->tmp_dir = $this->tmpFolder->create();

        $padesValid = $this->getMockBuilder("PadesValid")->disableOriginalConstructor()->getMock();
        $padesValid->expects($this->any())->method("validate")->willReturn(true);
        $this->getObjectInstancier()->set('PadesValid',$padesValid);
    }

    protected function tearDown() {
        parent::tearDown();
        $this->tmpFolder->delete($this->tmp_dir);
    }

	/**
	 * @throws Exception
	 */
    public function testValidateAllEmpty(){
        $logger = $this->getObjectInstancier()->get("Logger");
        $logger->setLogType(Logger::TYPE_MEMORY);
        $actesAnalyseFichierController = $this->getObjectInstancier()->get(ActesAnalyseFichierAEnvoyerWorker::class);
        $actesAnalyseFichierController->validateAllEnveloppe();
		$testHandler = $this->getObjectInstancier()->get("Monolog\Handler\TestHandler");
		$records = $testHandler->getRecords();
        $this->assertRegExp("#Lancement du script#",$records[0]['message']);
        $this->assertRegExp("#Analyse de 0 enveloppe de transaction à l'état POSTE#",$records[1]['message']);
        $this->assertRegExp("#Fin du script#",$records[2]['message']);
    }

	/**
	 * @throws Exception
	 */
    public function testValidateAllOne(){
        $transaction_id = $this->validateAll(__DIR__."/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");
        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,$transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,$transaction_info['status_id']);
        $this->assertEquals(
            "Accepté par le TdT : validation OK",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get("LogsSQL");
        $liste = $logsSQL->getLastLog();
        $this->assertRegExp("#Transaction.*[0-9]* : passage à l'état en attente#",$liste['message']);
    }

	/**
	 * @throws Exception
	 */
    public function testValidateAllOneBad(){
        $transaction_id = $this->validateAll(__DIR__."/../../fixtures/bad/SLO-EACT--214502494--20170717-5.tar.gz");
        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['status_id']);
        $this->assertEquals(
            "Enveloppe invalide : Le format text/plain du fichier 045-214502494-20170717-D201717-DE-1-1_1.txt n'est pas autorisé",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get("LogsSQL");
        $liste = $logsSQL->getLastLog();
        $this->assertRegExp("#Transaction.*[0-9]* : passage à l'état erreur#",$liste['message']);
    }

	/**
	 * @param $archivepath
	 * @return array|bool|mixed
	 * @throws Exception
	 */
    private function validateAll($archivepath){
		$actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);
		$transaction_id = $actesCreator->createTransaction(
			ActesStatusSQL::STATUS_POSTE,
			$archivepath,
			$this->tmp_dir
		);
		$actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
		$actesTransactionsSQL->setAntivirusCheck($transaction_id);
        $logger = $this->getObjectInstancier()->get("Logger");
        $logger->setLogType(Logger::TYPE_MEMORY);
        $actesAnalyseFichierController = $this->getObjectInstancier()->get(ActesAnalyseFichierAEnvoyerWorker::class);

        $actesAnalyseFichierController->validateAllEnveloppe();

        return $transaction_id;
    }

	/**
	 * @throws Exception
	 */
	public function testValidateAllOnePadesFailedRecoverable(){

		$padesValid = $this->getMockBuilder("PadesValid")->disableOriginalConstructor()->getMock();
		$padesValid->expects($this->any())->method("validate")->willThrowException(new RecoverableException("erreur de test"));
		$this->getObjectInstancier()->set('PadesValid',$padesValid);

		$transaction_id = $this->validateAll(__DIR__."/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");

		$actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
		$transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_POSTE,$transaction_info['last_status_id']);
		$transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_POSTE,$transaction_info['status_id']);

	}

	/**
	 * @throws Exception
	 */
	public function testValidateAllOnePadesFailedNotRecoverable(){

		$padesValid = $this->getMockBuilder("PadesValid")->disableOriginalConstructor()->getMock();
		$padesValid->expects($this->any())->method("validate")->willThrowException(new Exception("erreur de test"));
		$this->getObjectInstancier()->set('PadesValid',$padesValid);

		$transaction_id = $this->validateAll(__DIR__."/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");

		$actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
		$transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['last_status_id']);
		$transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['status_id']);
		$this->assertEquals(
			"Enveloppe invalide : erreur de test",
			$transaction_info['message']
		);
		$logsSQL = $this->getObjectInstancier()->get("LogsSQL");
		$liste = $logsSQL->getLastLog();
		$this->assertRegExp("#Transaction.*[0-9]* : passage à l'état erreur#",$liste['message']);


	}

}