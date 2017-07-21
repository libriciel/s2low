<?php

class ActesAnalyseFichierRecuControllerTest extends S2lowTestCase {

    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;
    private $tmp_dir2;

    /** @var  Logger */
    private $logger;

    protected function setUp(){
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->tmp_dir = $this->tmpFolder->create();
        $this->getObjectInstancier()->set('actes_response_tmp_local_path',$this->tmp_dir);

        $this->tmp_dir2 = $this->tmpFolder->create();
        $this->getObjectInstancier()->set('actes_response_error_path',$this->tmp_dir);


        $this->logger = $this->getObjectInstancier()->get("Logger");
        $this->logger->setLogType(Logger::TYPE_MEMORY);
    }

    protected function tearDown() {
        parent::tearDown();
        $this->tmpFolder->delete($this->tmp_dir);
        $this->tmpFolder->delete($this->tmp_dir2);
    }

    public function testAnalyseAllEmpty(){
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();

        $logs = $this->logger->getAllLog();
        $this->assertRegExp("#Aucun répertoire à analyser#",$logs[2]);
    }

    public function testAnalyseAllBadDirectory(){
        $bad_dir = $this->tmp_dir."/test_bad";
        mkdir($bad_dir);

        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();

        $this->assertFileExists($bad_dir);

        $logs = $this->logger->getAllLog();
        $this->assertRegExp("#Echec du traitement de $bad_dir#",$logs[4]);
        $this->assertRegExp("#Déplacement du répertoire test_bad#",$logs[5]);
    }

    public function testAnalyseAll(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);

        $actesTransactionsSQL = $this->getMockBuilder('ActesTransactionsSQL')
            ->setConstructorArgs(array($this->getSQLQuery()))
            ->setMethods(array('getBySirenAndNumeroInterne'))
            ->getMock();
        $actesTransactionsSQL->expects($this->any())->method('getBySirenAndNumeroInterne')->willReturn($transaction_id);

        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        $this->getObjectInstancier()->set('ActesTransactionsSQL',$actesTransactionsSQL);

        $dest = $this->tmp_dir."/test-archive-MISILCL";
        $src = __DIR__."/../fixtures/test-archive-MISILCL";

        `cp -r $src $dest`;


        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();


        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,$transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,$transaction_info['status_id']);
        $this->assertRegExp(
            "#Recu par le MIOCT le#",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get("LogsSQL");
        $liste = $logsSQL->getLastLog();
        $this->assertRegExp("#L'acte : [0-9]* passe en recu#",$liste['message']);
    }

    public function testAnalyseAllActeNotFound(){
        $dest = $this->tmp_dir."/test-archive-MISILCL";
        $src = __DIR__."/../fixtures/test-archive-MISILCL";

        `cp -r $src $dest`;

        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();
        $logs = $this->logger->getAllLog();

        $this->assertRegExp("#Aucune transation trouver pour le couple SIREN 000000000 - numéro interne 20170721D#",$logs[6]);
    }

    private function createTransaction($status){

        $sql="INSERT INTO actes_envelopes(user_id,file_path) VALUES(1,?) returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql,"foo");


        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,antivirus_check) VALUES (?,?,?,?,?) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,true);


        return $transaction_id;
    }



    public function testAnalyseAllActeErrorRep(){
        $this->getObjectInstancier()->set('actes_response_tmp_local_path',$this->tmp_dir."/not-exists/");

        $this->setExpectedException("Exception","Erreur lors de la lecture du répertoire  {$this->tmp_dir}/not-exists/");
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();
        $logs = $this->logger->getAllLog();

        $this->assertRegExp("#Aucune transation trouver pour le couple SIREN 000000000 - numéro interne 20170721D#",$logs[6]);
    }

}