<?php

require_once __DIR__."/ActesCreator.php";

class ActesAnalyseFichierControllerTest extends S2lowTestCase {

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

    public function testValidateAllEmpty(){
        $logger = $this->getObjectInstancier()->get("Logger");
        $logger->setLogType(Logger::TYPE_MEMORY);
        $actesAnalyseFichierController = $this->getObjectInstancier()->get('ActesAnalyseFichierController');
        $actesAnalyseFichierController->validateAllEnveloppe();
        $this->assertRegExp("#Lancement du script#",$logger->getAllLog()[0]);
        $this->assertRegExp("#Analyse de 0 enveloppe de transaction à l'état POSTE#",$logger->getAllLog()[1]);
        $this->assertRegExp("#Fin du script#",$logger->getAllLog()[2]);
    }

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
        $actesAnalyseFichierController = $this->getObjectInstancier()->get('ActesAnalyseFichierController');

        $actesAnalyseFichierController->validateAllEnveloppe();

        return $transaction_id;
    }

}