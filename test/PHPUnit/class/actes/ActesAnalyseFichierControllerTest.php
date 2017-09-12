<?php

class ActesAnalyseFichierControllerTest extends S2lowTestCase {

    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;

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
            "Accepte par le TdT : validation OK",
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
        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_POSTE,
            $archivepath
        );

        $logger = $this->getObjectInstancier()->get("Logger");
        $logger->setLogType(Logger::TYPE_MEMORY);
        $actesAnalyseFichierController = $this->getObjectInstancier()->get('ActesAnalyseFichierController');

        $actesAnalyseFichierController->validateAllEnveloppe();

        return $transaction_id;
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