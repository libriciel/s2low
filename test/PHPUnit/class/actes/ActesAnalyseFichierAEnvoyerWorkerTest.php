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

	private function getActesAnalysFichierAEnvoyerWorker(){
    	return  $this->getObjectInstancier()->get(ActesAnalyseFichierAEnvoyerWorker::class);
	}

    protected function tearDown() {
        parent::tearDown();
        $this->tmpFolder->delete($this->tmp_dir);
    }

	public function testQueueName(){
		$this->assertEquals(
			ActesAnalyseFichierAEnvoyerWorker::QUEUE_NAME,
			$this->getActesAnalysFichierAEnvoyerWorker()->getQueueName()
		);
	}

	public function testGetId(){
		$this->assertEquals(
			42,
			$this->getActesAnalysFichierAEnvoyerWorker()->getData(42)
		);
	}

	public function testGetList(){
		$data = $this->createOneTransaction(__DIR__."/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz");
		$result = $this->getActesAnalysFichierAEnvoyerWorker()->getAllId();
		$this->assertEquals([$data['envelope_id']],$result);
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
		$data = $this->createOneTransaction($archivepath);
        $this->getActesAnalysFichierAEnvoyerWorker()->work($data['envelope_id']);
        return $data['transaction_id'];
    }

    private function createOneTransaction($archivepath){
		$actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);
		$transaction_id = $actesCreator->createTransaction(
			ActesStatusSQL::STATUS_POSTE,
			$archivepath,
			$this->tmp_dir
		);
		$envelope_id = $actesCreator->getLastEnvelopeId();
		$actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
		$actesTransactionsSQL->setAntivirusCheck($transaction_id);
		return ['envelope_id'=>$envelope_id,'transaction_id'=>$transaction_id];
	}

	/**
	 * @throws Exception
	 */
	public function testValidateAllOnePadesFailedRecoverable(){

		$padesValid = $this->getMockBuilder("PadesValid")->disableOriginalConstructor()->getMock();
		$padesValid->expects($this->any())->method("validate")->willThrowException(new RecoverableException("erreur de test"));
		$this->getObjectInstancier()->set('PadesValid',$padesValid);

		$this->setExpectedException(RecoverableException::class,"erreur de test");
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