<?php

class ActesEnvoiFichierControllerTest extends S2lowTestCase {

    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;

    protected function setUp(){
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->tmp_dir = $this->tmpFolder->create();
        $this->getObjectInstancier()->set("actes_appli_trigramme","SLO");
        $logger = $this->getObjectInstancier()->get("Logger");
        $logger->setLogType(Logger::TYPE_MEMORY);

        $actesFileSender = $this->getMockBuilder('ActesFileSender')->disableOriginalConstructor()->getMock();
        $this->getObjectInstancier()->set('ActesFileSender',$actesFileSender);
    }

    protected function tearDown() {
        parent::tearDown();
        $this->tmpFolder->delete($this->tmp_dir);
    }

    public function testValidateAllEmpty(){
        $logger = $this->getObjectInstancier()->get("Logger");
        $logger->setLogType(Logger::TYPE_MEMORY);
        $actesEnvoiFichierController = $this->getObjectInstancier()->get('ActesEnvoiFichierController');
        $actesEnvoiFichierController->sendAllEnvelopes();
        $this->assertRegExp("#Lancement du script#",$logger->getAllLog()[0]);
        $this->assertRegExp("#Envoie de 0 enveloppes de transaction à l'état EN ATTENTE DE TRANSMISSION#",$logger->getAllLog()[1]);
        $this->assertRegExp("#Fin du script#",$logger->getAllLog()[2]);
    }

    public function testEnvoiUneEnveloppe(){
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            __DIR__."/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz"
        );

        $actesEnvoiFichierController = $this->getObjectInstancier()->get('ActesEnvoiFichierController');
        $actesEnvoiFichierController->sendAllEnvelopes();
        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS,$transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS,$transaction_info['status_id']);
        $this->assertEquals(
            "Transmis au MIOCT",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get("LogsSQL");
        $liste = $logsSQL->getLastLog();
        $this->assertRegExp("#Transaction.*[0-9]* : passage à l'état transmis#",$liste['message']);
    }

    public function testEnvoiUneEnveloppeFailed(){
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,
            __DIR__."/../../fixtures/ok/SLO-EACT--214502494--20170717-5.tar.gz"
        );
        /** @var PHPUnit_Framework_MockObject_MockObject $actesFileSender */
        $actesFileSender = $this->getObjectInstancier()->get('ActesFileSender');

        $actesFileSender->expects($this->any())->method("send")->willThrowException(new Exception("Erreur du mock"));

        $actesEnvoiFichierController = $this->getObjectInstancier()->get('ActesEnvoiFichierController');
        $actesEnvoiFichierController->sendAllEnvelopes();
        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION,$transaction_info['last_status_id']);

        $log = $this->getObjectInstancier()->get("Logger")->getAllLog();
        $this->assertRegExp("#Erreur du mock#",$log[3]);
    }

    private function createTransaction($status,$archive_path){
        $archive_name = basename($archive_path);

        copy($archive_path,$this->tmp_dir."/$archive_name");

        $sql="INSERT INTO actes_envelopes(user_id,file_path) VALUES(1,?) returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql,basename($this->tmp_dir)."/$archive_name");


        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,antivirus_check) VALUES (?,?,?,?,?) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,true);

        return $transaction_id;
    }
}
