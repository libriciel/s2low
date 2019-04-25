<?php

class ActesAnalyseFichierRecuControllerTest extends S2lowTestCase {

	const TEST_ARCHIVE_MISILCL_PATH = __DIR__."/../fixtures/test-archive-MISILCL";

    /** @var  TmpFolder */
    private $tmpFolder;
    private $tmp_dir;
    private $tmp_dir2;
    private $actes_files_upload_root;

    private $actes_ministere_acronyme;

    protected function setUp(){
        parent::setUp();
        $this->tmpFolder = new TmpFolder();
        $this->tmp_dir = $this->tmpFolder->create();
        $this->getObjectInstancier()->set('actes_response_tmp_local_path',$this->tmp_dir);

        $this->tmp_dir2 = $this->tmpFolder->create();
        $this->getObjectInstancier()->set('actes_response_error_path',$this->tmp_dir2);

        $this->actes_files_upload_root = $this->tmpFolder->create();
        $this->getObjectInstancier()->set('actes_files_upload_root',$this->actes_files_upload_root);

        $this->actes_ministere_acronyme = ACTES_MINISTERE_ACRONYME;

    }

    protected function tearDown() {
        parent::tearDown();
        $this->tmpFolder->delete($this->tmp_dir);
        $this->tmpFolder->delete($this->tmp_dir2);
        $this->tmpFolder->delete($this->actes_files_upload_root);
    }

	/**
	 * @throws Exception
	 */
    public function testAnalyseAllEmpty(){
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
        $actesAnalyseFichierRecuController->analyseAll();
        $logs = $this->getLogRecords();
        $this->assertEquals("Aucun répertoire à analyser",$logs[2][S2lowLogger::MESSAGE]);
    }

	/**
	 * @throws Exception
	 */
    public function testAnalyseAllBadDirectory(){
        $bad_dir = $this->tmp_dir."/test_bad";
        mkdir($bad_dir);

        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
        $actesAnalyseFichierRecuController->analyseAll();

        $this->assertFileExists($this->tmp_dir2."/test_bad");

        $logs = $this->getLogRecords();
        $this->assertEquals("Echec du traitement de $bad_dir : Aucun fichier de type enveloppe métier n'a été trouvé dans le répertoire $bad_dir",$logs[4][S2lowLogger::MESSAGE]);
        $this->assertEquals("Déplacement du répertoire test_bad vers {$this->tmp_dir2}",$logs[5][S2lowLogger::MESSAGE]);
    }


    private function mockGetBySirenAndNumeroInterne($transaction_id){
        $actesTransactionsSQL = $this->getMockBuilder(ActesTransactionsSQL::class)
            ->setConstructorArgs(array($this->getSQLQuery()))
            ->setMethods(array('getBySirenAndNumeroInterne'))
            ->getMock();
        $actesTransactionsSQL->expects($this->any())->method('getBySirenAndNumeroInterne')->willReturn($transaction_id);
        $this->getObjectInstancier()->set(ActesTransactionsSQL::class,$actesTransactionsSQL);
        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        return $actesTransactionsSQL;
    }

	/**
	 * @throws Exception
	 */
    public function testAnalyseAll(){

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);

        $actesTransactionsSQL =  $this->mockGetBySirenAndNumeroInterne($transaction_id);

        $this->copyDirectoryToAnalysePath( self::TEST_ARCHIVE_MISILCL_PATH);

        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
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

        $this->assertEquals(array('.','..'),scandir("{$this->tmp_dir}"));
    }

	/**
	 * @throws Exception
	 */
    public function testAnalyseAllActeNotFound(){
        $this->copyDirectoryToAnalysePath( self::TEST_ARCHIVE_MISILCL_PATH);
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
        $actesAnalyseFichierRecuController->analyseAll();
        $logs = $this->getLogRecords();
        $this->assertRegExp("#Aucune transation trouver pour le couple SIREN 000000000 - numéro interne 20170721D#",$logs[6][S2lowLogger::MESSAGE]);
    }

    private function createTransaction($status){
        $envelope_id = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class)->create(
            1,
            "000000000/abc-EACT--210703385--20170612-2.tar.gz"
        );

        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,antivirus_check,number,type) VALUES (?,?,?,?,?,?,?) returning ID;";
        return $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,true,'20170721D',1);
    }

	/**
	 * @throws Exception
	 */
    public function testAnalyseAllActeErrorRep(){
        $this->getObjectInstancier()->set('actes_response_tmp_local_path',$this->tmp_dir."/not-exists/");

        try {
			$actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
			$actesAnalyseFichierRecuController->analyseAll();
		} catch (Exception $e){
        	$this->assertEquals("Erreur lors de la lecture du répertoire  {$this->tmp_dir}/not-exists/",$e->getMessage());
		}
        $logs = $this->getLogRecords();
        $this->assertEquals("Erreur lors de la lecture du répertoire  {$this->tmp_dir}/not-exists/",$logs[2]['message']);
    }

	/**
	 * @throws Exception
	 */
    public function testEnveloppeAnomalie(){
        $this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-anomalie");
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
        $actesAnalyseFichierRecuController->analyseAll();

        $actesTransactionsSQL= $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['last_status_id']);
        $transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['status_id']);
        $this->assertRegExp(
            "#Enveloppe rejetée par le {$this->actes_ministere_acronyme}#",
            $transaction_info['message']
        );
        $this->assertEquals(array('.','..'),scandir("{$this->tmp_dir}"));
    }

	/**
	 * @throws Exception
	 */
	public function testMessageMetierAnomalie(){
		$this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-message-anomalie");
		$transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
		$actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
		$actesAnalyseFichierRecuController->analyseAll();

		$actesTransactionsSQL= $this->getObjectInstancier()->get('ActesTransactionsSQL');
		$transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

		$this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['last_status_id']);
		$transaction_info = $actesTransactionsSQL->getLastTransactionWorkflowInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR,$transaction_info['status_id']);
		$this->assertRegExp(
			"#Anomalie signalee par le MI : 042 - Ca ne fonctionne pas#",
			$transaction_info['message']
		);
		$this->assertEquals(array('.','..'),scandir("{$this->tmp_dir}"));
	}


	/**
	 * @throws Exception
	 */
	public function testMessageMetierAnomalieAfterAcquitter(){
		$this->copyDirectoryToAnalysePath( self::TEST_ARCHIVE_MISILCL_PATH);


		$transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
		$actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
		$actesAnalyseFichierRecuController->analyseAll();


		$this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-message-anomalie");
		$actesAnalyseFichierRecuController->analyseAll();

		$actesTransactionsSQL= $this->getObjectInstancier()->get('ActesTransactionsSQL');
		$transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

		$this->assertEquals(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,$transaction_info['last_status_id']);
	}


	/**
	 * @throws Exception
	 */
    public function testCourrierSimple(){
        $transaction_id_orig = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id_orig);
        mkdir($this->actes_files_upload_root."/000000000/20170725A/",0777,true);
        $this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-courrier-simple");
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1,$enveloppe_info['user_id']);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesTransactionSQL->getIdByEnvelopeId($enveloppe_info['id']);

        $info = $actesTransactionSQL->getInfo($transaction_id);

		$this->assertNotEmpty($actesTransactionSQL->getRelatedTransaction($transaction_id_orig));


		$this->assertEquals("2017-07-25",substr($info['decision_date'],0,10));
    }

	/**
	 * @throws Exception
	 */
    public function testDefereTA(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $this->mockGetBySirenAndNumeroInterne($transaction_id);
        mkdir($this->actes_files_upload_root."/000000000/20170725A/",0777,true);
        $this->copyDirectoryToAnalysePath( __DIR__."/../fixtures/test-defere-ta");
        $actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
        $actesAnalyseFichierRecuController->analyseAll();

        $actesEnveloppeSQL = $this->getObjectInstancier()->get("ActesEnvelopeSQL");
        $enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
        $this->assertEquals(1,$enveloppe_info['user_id']);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $this->assertNotEmpty($actesTransactionSQL->getRelatedTransaction($transaction_id));
    }


    private function copyDirectoryToAnalysePath($directory){
    	exec("cp -r $directory {$this->tmp_dir}/test");
    }

	/**
	 * @throws Exception
	 */
	public function testCourrierSimpleRenumerote()
	{
		$transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
		$this->mockGetBySirenAndNumeroInterne($transaction_id);
		mkdir($this->actes_files_upload_root . "/000000000/20170725A/", 0777, true);
		$this->copyDirectoryToAnalysePath(__DIR__ . "/../fixtures/test-courrier-simple-renumerote");
		$actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
		$actesAnalyseFichierRecuController->analyseAll();

		$actesEnveloppeSQL = $this->getObjectInstancier()->get("ActesEnvelopeSQL");
		$enveloppe_info = $actesEnveloppeSQL->getLastEnvelope();
		$this->assertEquals(1, $enveloppe_info['user_id']);

		$actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

		$transaction_id = $actesTransactionSQL->getIdByEnvelopeId($enveloppe_info['id']);

		$info = $actesTransactionSQL->getInfo($transaction_id);

		$this->assertEquals("2017-07-25", substr($info['decision_date'], 0, 10));

		$actesRetriever = $this->getObjectInstancier()->get(ActesRetriever::class);

		$path = $actesRetriever->getPath($enveloppe_info['file_path']);


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
	public function testMessageMultiCanal() {
		$transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
		$this->mockGetBySirenAndNumeroInterne($transaction_id);
		mkdir($this->actes_files_upload_root . "/000000000/20170725A/", 0777, true);
		$this->copyDirectoryToAnalysePath(__DIR__ . "/fixtures/mail-suite-multicanal/");
		$actesAnalyseFichierRecuController = $this->getObjectInstancier()->get(ActesAnalyseFichierRecuController::class);
		$actesAnalyseFichierRecuController->analyseAll();
		$this->assertEquals(array('.','..'),scandir("{$this->tmp_dir}"));
		$logs = $this->getLogRecords();
		$this->assertRegExp("#Message de réponse à un multicanal#",$logs[4][S2lowLogger::MESSAGE]);
		$this->assertRegExp("#Suppression du répertoire#",$logs[5]['message']);

		$actesTransactionsSQL =  $this->mockGetBySirenAndNumeroInterne($transaction_id);
		$transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
		$this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS,$transaction_info['last_status_id']);
	}

}