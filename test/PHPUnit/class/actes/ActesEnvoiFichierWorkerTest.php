<?php

class ActesEnvoiFichierWorkerTest extends S2lowTestCase {

    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;

    protected function setUp(){
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->tmp_dir = $this->tmpFolder->create();
        $this->getObjectInstancier()->set("actes_appli_trigramme","SLO");

        $actesFileSender = $this->getMockBuilder(ActesFileSender::class)->disableOriginalConstructor()->getMock();
        $this->getObjectInstancier()->set(ActesFileSender::class,$actesFileSender);
    }

    protected function tearDown() {
        parent::tearDown();
        $this->tmpFolder->delete($this->tmp_dir);
    }

    public function testValidateAllEmpty(){
        $actesEnvoiFichierController = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);
        $actesEnvoiFichierController->sendAllEnvelopes();
        $logs = $this->getLogRecords();
        $this->assertRegExp("#Lancement du script#",$logs[0]['message']);
        $this->assertRegExp("#Envoie de 0 enveloppes de transaction à l'état EN ATTENTE DE TRANSMISSION#",$logs[1]['message']);
        $this->assertRegExp("#Fin du script#",$logs[2]['message']);
    }

    public function testEnvoiUneEnveloppe(){
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            __DIR__."/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz"
        );

        $actesEnvoiFichierController = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);
        $actesEnvoiFichierController->sendAllEnvelopes();
        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS,$transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS,$transaction_info['status_id']);
        $this->assertEquals(
            "Transmis au MI",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get("LogsSQL");
        $liste = $logsSQL->getLastLog();
        $this->assertRegExp("#Transaction.*[0-9]* : passage à l'état transmis#",$liste['message']);
    }

	public function testEnvoiUneEnveloppeMauvaisEtat(){
		$transaction_id = $this->createTransaction(
			ActesStatusSQL::STATUS_TRANSMIS,
			__DIR__."/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz"
		);

		$actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
		$transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

		$envelope_id = $transaction_info['envelope_id'];

		$actesEnvoiFichierController = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);

		$actesEnvoiFichierController->work($envelope_id);


		$transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS,$transaction_info['last_status_id']);
		$transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS,$transaction_info['status_id']);
		$this->assertEquals(
			"Creation",
			$transaction_info['message']
		);

		$this->assertEquals(
			"La transaction $transaction_id à poster n'est pas en attente de transmission : état 3 trouvé",
			$this->getLogRecords()[0]['message']
		);
	}

    public function testEnvoiUneEnveloppeFailed(){
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            __DIR__."/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz"
        );
        /** @var PHPUnit_Framework_MockObject_MockObject $actesFileSender */
        $actesFileSender = $this->getObjectInstancier()->get('ActesFileSender');

        $actesFileSender->expects($this->any())->method("send")->willThrowException(new Exception("Erreur du mock"));

        $actesEnvoiFichierController = $this->getObjectInstancier()->get(ActesEnvoiFichierWorker::class);
        $actesEnvoiFichierController->sendAllEnvelopes();
        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,$transaction_info['last_status_id']);

		$logs = $this->getLogRecords();
        $this->assertRegExp("#Erreur du mock#",$logs[3]['message']);
    }

    private function createTransaction($status,$archive_path){
        $archive_name = basename($archive_path);

        copy($archive_path,$this->tmp_dir."/$archive_name");

        $sql="INSERT INTO actes_envelopes(user_id,file_path) VALUES(1,?) returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql,basename($this->tmp_dir)."/$archive_name");


        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,antivirus_check) VALUES (?,?,?,?,?) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,true);

        $sql = "INSERT INTO actes_transactions_workflow(transaction_id, status_id, date, message, flux_retour) VALUES (?,?,now(),?,?)";
        $this->getSQLQuery()->queryOne($sql,$transaction_id,$status,"Creation","");
        return $transaction_id;
    }
}
