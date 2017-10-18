<?php

class ActesAnalyseFichierRecuControllerTest extends S2lowTestCase {

    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;
    private $tmp_dir2;
    private $actes_files_upload_root;

    /** @var  Logger */
    private $logger;

    private $actes_ministere_acronyme;

    protected function setUp(){
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->tmp_dir = $this->tmpFolder->create();
        $this->getObjectInstancier()->set('actes_response_tmp_local_path',$this->tmp_dir);

        $this->tmp_dir2 = $this->tmpFolder->create();
        $this->getObjectInstancier()->set('actes_response_error_path',$this->tmp_dir);

        $this->actes_files_upload_root = $this->tmpFolder->create();
        $this->getObjectInstancier()->set('actes_files_upload_root',$this->actes_files_upload_root);


        $this->logger = $this->getObjectInstancier()->get("Logger");
        $this->logger->setLogType(Logger::TYPE_MEMORY);

        $this->actes_ministere_acronyme = ACTES_MINISTERE_ACRONYME;

    }

    protected function tearDown() {
        parent::tearDown();
        $this->tmpFolder->delete($this->tmp_dir);
        $this->tmpFolder->delete($this->tmp_dir2);
        $this->tmpFolder->delete($this->actes_files_upload_root);
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


    private function mockGetBySirenAndNumeroInterne($transaction_id){
        $actesTransactionsSQL = $this->getMockBuilder('ActesTransactionsSQL')
            ->setConstructorArgs(array($this->getSQLQuery()))
            ->setMethods(array('getBySirenAndNumeroInterne'))
            ->getMock();
        $actesTransactionsSQL->expects($this->any())->method('getBySirenAndNumeroInterne')->willReturn($transaction_id);
        $this->getObjectInstancier()->set('ActesTransactionsSQL',$actesTransactionsSQL);
        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        return $actesTransactionsSQL;
    }

    public function testAnalyseAll(){

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);

        $actesTransactionsSQL =  $this->mockGetBySirenAndNumeroInterne($transaction_id);

        $this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-archive-MISILCL");


        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,$transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,$transaction_info['status_id']);
        $this->assertRegExp(
            "#Reçu par le {$this->actes_ministere_acronyme} le#",
            $transaction_info['message']
        );
        $logsSQL = $this->getObjectInstancier()->get("LogsSQL");
        $liste = $logsSQL->getLastLog();
        $this->assertRegExp("#Transaction.*[0-9]* : passage à l'état acquittement reçu#",$liste['message']);
    }

    public function testAnalyseAllActeNotFound(){
        $this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-archive-MISILCL");
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();
        $logs = $this->logger->getAllLog();

        $this->assertRegExp("#Aucune transation trouver pour le couple SIREN 000000000 - numéro interne 20170721D#",$logs[6]);
    }

    private function createTransaction($status){
        $envelope_id = $this->getObjectInstancier()->get("ActesEnvelopeSQL")->create(
            1,
            "000000000/abc-EACT--210703385--20170612-2.tar.gz"
        );

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

    public function testEnveloppeAnomalie(){
        $this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-anomalie");
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();

        $actesTransactionsSQL= $this->getObjectInstancier()->get('ActesTransactionsSQL');
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['status_id']);
        $this->assertRegExp(
            "#Enveloppe rejetée par le {$this->actes_ministere_acronyme}#",
            $transaction_info['message']
        );
    }

    public function testCourrierSimple(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id);
        mkdir($this->actes_files_upload_root."/000000000/20170725A/",0777,true);
        $this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-courrier-simple");
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = $this->getObjectInstancier()->get("ActesEnvelopeSQL");
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1,$enveloppe_info['user_id']);
    }

    public function testDefereTA(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id);
        mkdir($this->actes_files_upload_root."/000000000/20170725A/",0777,true);
        $this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-defere-ta");
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get("ActesAnalyseFichierRecuController");
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = $this->getObjectInstancier()->get("ActesEnvelopeSQL");
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1,$enveloppe_info['user_id']);
    }


    private function copyDirectoryToAnalysePath($directory){
        `cp -r $directory {$this->tmp_dir}/test`;
    }
}