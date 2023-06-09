<?php

use S2low\Services\Helios\DGFiPConnection\DGFiPConnection;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionMode;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager;
use S2low\Services\Helios\HeliosEnvoiControler;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\helios\FichierCompteur;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosEnvoiControlerTest extends \S2low\Tests\S2lowSymfonyWebTestCase
{
    private $testStreamUrl;

    /** @var  HeliosController */
    private $heliosController;

    /** @var  HeliosEnvoiControler */
    private $heliosEnvoiControler;

    /** @var  TmpFolder */
    private $tmpFolder;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpFolder = new TmpFolder();
        $this->testStreamUrl = $this->tmpFolder->create();

        $this->counterDir = $this->tmpFolder->create();
        $counterFile = fopen($this->counterDir . "/counter.txt", "w");
        fwrite($counterFile, "000");

        $this->workerScript = $this->getMockBuilder(\S2lowLegacy\Class\WorkerScript::class)
            ->disableOriginalConstructor()->getMock();
        $this->dgfipConnectionBuilderMock = $this->getMockBuilder(DGFiPConnectionBuilder::class)
            ->disableOriginalConstructor()->getMock();

        mkdir($this->testStreamUrl . "/helios");
        $this->getObjectInstancier()->set("helios_files_upload_root", $this->testStreamUrl . "/helios/");
        $this->heliosController = new HeliosController($this->getObjectInstancier());
        $this->heliosEnvoiControler = new HeliosEnvoiControler(
            $this->getContainer()->get(SQLQuery::class),
            $this->getContainer()->get(PesAllerRetriever::class),
            $this->getContainer()->get(Antivirus::class),
            $this->workerScript,
            $this->getContainer()->get(MailerSymfonyFactory::class),
            new DGFiPConnectionsManager(
                "helios_ftp_p_appli",
                "std_server",
                1982,
                "std_login",
                "std_password",
                "std_response_server_path",
                "std_sending_destination",
                DGFiPConnectionMode::GATEWAY,
                true,
                "passtrans_server",
                1982,
                "passtrans_login",
                "passtrans_password",
                DGFiPConnectionMode::PASSTRANS_SFTP,
                true,
                "passtrans_sending_destination",
                "passtrans_response_server_path",
                $this->dgfipConnectionBuilderMock
            ),
            new FichierCompteur($this->counterDir . '/counter.txt')
        );
    }

    protected function tearDown(): void
    {
        $this->tmpFolder->delete($this->testStreamUrl);
        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testValidateAllTransactions()
    {
        $this->validatePesAller("pes_aller_ok.xml");
        $authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
        $siret_list = $authoritySiret->siretList(1);
        $this->assertEquals("12345678912345", $siret_list[0]['siret']);
    }

    /**
     * @param $filename
     * @return array|bool|mixed
     * @throws Exception
     */
    private function validatePesAller($filename)
    {
        $id_t = $this->getImportFile($filename);
        $this->validate($id_t);
        return $id_t;
    }

    /**
     * @throws Exception
     */
    public function testNotInIso8859()
    {
        $this->workerScript->expects($this->never())->method('putJobByQueueName');
        $id_t = $this->validatePesAller("pes_aller_utf8.xml");

        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals(HeliosTransactionsSQL::ERREUR, $info['last_status_id']);
        $last_status_info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals("Transaction $id_t : ce fichier n'est pas encodé en ISO-8859-1", $last_status_info['message']);
    }

    public function testCodCollTropLong()
    {
        $this->workerScript->expects($this->never())->method('putJobByQueueName');
        $id_t = $this->validatePesAller("pes_aller_CodColTropLong.xml");

        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals(HeliosTransactionsSQL::ERREUR, $info['last_status_id']);
        $last_status_info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals("Transaction $id_t : Le CodCol est trop long", $last_status_info['message']);
    }

    /**
     * @throws Exception
     */
    public function testAccentNomFic()
    {
        $this->workerScript->expects($this->once())->method('putJobByQueueName');
        $id_t = $this->validatePesAller("PESALR2_accent_dans_nomfic.xml");

        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);

        $this->assertEquals("PESALR220001861200016Trésorerie_de_M20141205152530.xml", $info['xml_nomfic']);

        $authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
        $siret_list = $authoritySiret->siretList(1);
        $this->assertEquals("12345678912345", $siret_list[0]['siret']);
    }

    /**
     * @throws Exception
     */
    public function testRetrieveAllPesInfo()
    {
        $id_t = $this->validatePesAller("pes_aller_ok.xml");
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals("03f432a4f6d35110bf309fb525eb61f7", $info['xml_nomfic']);
        $this->assertEquals("123", $info['xml_cod_col']);
        $this->assertEquals("12", $info['xml_cod_bud']);
        $this->assertEquals("034000", $info['xml_id_post']);
    }

    /**
     * @throws Exception
     */
    public function testChangedPesAller()
    {
        $this->workerScript->expects($this->never())->method('putJobByQueueName');
        $pes_aller = __DIR__ . "/../../../test/PHPUnit/helios/fixtures/pes_aller_ok.xml";
        copy($pes_aller, $this->testStreamUrl . "/helios/" . sha1_file($pes_aller));
        $id_t = $this->heliosController->importFile(8, $pes_aller, "pes_aller.xml");
        $pes_aller_change = __DIR__ . "/../../../test/PHPUnit/helios/fixtures/pes_aller.xml";
        copy($pes_aller_change, $this->testStreamUrl . "/helios/" . sha1_file($pes_aller));
        ob_start();
        $this->heliosEnvoiControler->validateOneTransaction($id_t);
        ob_end_clean();

        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(-1, $info['status_id']);
        $this->assertMatchesRegularExpression("#Le fichier a été modifé depuis son postage sur la plateforme#", $info['message']);
    }

    public function testSigneNoID()
    {
        $id_t = $this->getImportFile("/../../class/fixtures/pes_no_id.xml");
        $this->workerScript->expects($this->once())->method('putJobByQueueName')
            ->with('helios-envoi', "$id_t");
        $this->validate($id_t);
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(HeliosTransactionsSQL::ATTENTE, $info['status_id']);
        $this->assertMatchesRegularExpression("#Transaction $id_t dans la file d'attente#", $info['message']);
    }

    public function testSigneNoIDPasstrans()
    {
        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $authoritySQL->query("UPDATE authorities SET helios_use_passtrans = true WHERE id =1");
        $id_t = $this->getImportFile("/../../class/fixtures/pes_no_id.xml");
        $this->workerScript->expects($this->once())->method('putJobByQueueName')
            ->with('helios-envoi-passtrans', "$id_t");
        $this->validate($id_t);
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(HeliosTransactionsSQL::ATTENTE, $info['status_id']);
        $this->assertMatchesRegularExpression("#Transaction $id_t dans la file d'attente#", $info['message']);
    }

    public function testDejaSigneBadSignature()
    {
        $this->workerScript->expects($this->never())->method('putJobByQueueName');
        $id_t = $this->validatePesAller("/../../lib/fixtures/HELIOS_SIMU_ALR2_bad_signature.xml");
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(-1, $info['status_id']);
        $this->assertMatchesRegularExpression("#La signature du fichier est invalide#", $info['message']);
    }

    /**
     * @throws Exception
     */
    public function testSendSamePESAller()
    {
        $this->sendSamePESAllerFailed();
    }

    /**
     * @throws Exception
     */
    private function sendSamePESAllerFailed()
    {
        $this->validatePesAller("pes_aller_ok.xml");
        $id_t = $this->validatePesAller("pes_aller_ok.xml");
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());

        $info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(-1, $info['status_id']);
        $this->assertMatchesRegularExpression("#ce fichier existe déjà sur la plateforme#", $info['message']);
    }


    /**
     * @throws Exception
     */
    public function testSendSamePESAllerDoNotVerify()
    {
        $this->workerScript->expects($this->exactly(2))->method('putJobByQueueName');
        $this->heliosEnvoiControler->setDoNotVerifyNomFicUnicity(true);
        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $authoritySQL->updateDoNotVerifyNomFicUnicity(1, true);
        $this->validatePesAller("pes_aller_ok.xml");
        $id_t = $this->validatePesAller("pes_aller_ok.xml");
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(2, $info['status_id']);
    }

    /**
     * @throws Exception
     */
    public function testSendSamePESAllerDoNotVerifyOnlyConst()
    {
        $this->heliosEnvoiControler->setDoNotVerifyNomFicUnicity(true);
        $this->sendSamePESAllerFailed();
    }

    /**
     * @throws Exception
     */
    public function testSendSamePESAllerDoNotVerifyOnlyAuthority()
    {
        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $authoritySQL->updateDoNotVerifyNomFicUnicity(1, true);
        $this->sendSamePESAllerFailed();
    }

    /**
     * @throws Exception
     */
    public function testWhenPESAllerIsEmpty()
    {
        $this->workerScript->expects($this->never())->method('putJobByQueueName');
        $id_t = $this->validatePesAller("pes_aller_empty.xml");
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals(-1, $info['last_status_id']);
        $last_status_info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(
            "Transaction $id_t : ce fichier ne contient ni bordereau, ni PJ, ni marché",
            $last_status_info['message']
        );
    }

    private function createPesAllerToSend($filename)
    {
        $id_t = $this->getImportFile($filename);
        $this->validate($id_t);
        return $id_t;
    }

    /**
     * Quand on envoie une transaction d'une autorité non Passtrans sur la file passtrans,
     * 1/ elle se retrouve en erreur
     * 2/ l'envoi ne se fait pas ( dgfipConnectionBuilderMock non appelé )
     * @return void
     */
    public function testSendOneTransactionMauvaiseFilePasstrans()
    {
        $this->dgfipConnectionBuilderMock->expects($this->never())->method('get');

        $id_t = $this->createPesAllerToSend("pes_aller_ok.xml");
        ob_start();
        $this->heliosEnvoiControler->sendOneTransaction($id_t, true);
        ob_end_clean();
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals(HeliosTransactionsSQL::ERREUR, $info['last_status_id']);
        $last_status_info = $heliosTransaction->getLastStatusInfo($id_t);

        $this->assertEquals(
            "Transaction $id_t : la transaction a été aiguillée sur la mauvaise file passtrans",
            $last_status_info['message']
        );
    }

    /**
     * Quand on envoie une transaction d'une autorité Passtrans sur la file non passtrans,
     * 1/ elle se retrouve en erreur aussi
     * 2/ l'envoi ne se fait pas ( dgfipConnectionBuilderMock non appelé )
     * @return void
     */
    public function testSendOneTransactionMauvaiseFilePasstrans2()
    {
        $this->dgfipConnectionBuilderMock->expects($this->never())->method('get');

        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $authoritySQL->query("UPDATE authorities SET helios_use_passtrans = true WHERE id =1");

        $id_t = $this->createPesAllerToSend("pes_aller_ok.xml");
        ob_start();
        $this->heliosEnvoiControler->sendOneTransaction($id_t, false);
        ob_end_clean();
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals(HeliosTransactionsSQL::ERREUR, $info['last_status_id']);
        $last_status_info = $heliosTransaction->getLastStatusInfo($id_t);

        $this->assertEquals(
            "Transaction $id_t : la transaction a été aiguillée sur la mauvaise file passtrans",
            $last_status_info['message']
        );
    }

    /**
     * Quand on envoie une transaction d'une autorité non Passtrans sur la file non passtrans, elle est
     * correctement envoyée :
     * 1/ les paramètres du serveur sont corrects (std_server, etc)
     * 2/ sendFileOnUniqueConnection est bien appelé avec un nommage correct
     * 3/ le passage à transmis se fait bien
     * @return void
     */
    public function testSendOneTransactionBonneFilePasstrans()
    {
        $id_t = $this->createPesAllerToSend("pes_aller_ok.xml");

        $dgfipConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()->getMock();

        $this->dgfipConnectionBuilderMock->expects($this->once())->method('get')
            ->with(
                new DGFiPConnectionConfiguration(
                    'std_server',
                    1982,
                    'std_login',
                    'std_password',
                    DGFiPConnectionMode::GATEWAY,
                    true,
                    'std_sending_destination',
                    'std_response_server_path',
                    'helios_ftp_p_appli'
                )
            )->willReturn($dgfipConnection);
        $dgfipConnection->expects($this->once())->method("sendFileOnUniqueConnection")
            ->with(
                'helios_ftp_dest',
                'PES#123#034000#12',
                "/data/tdt-workspace/helios/sending-tmp//PESALR2_123456789_" . date("ymd") . "_001.xml"
            );
        ob_start();
        $this->heliosEnvoiControler->sendOneTransaction($id_t, false);
        ob_end_clean();
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals(HeliosTransactionsSQL::TRANSMIS, $info['last_status_id']);
        $last_status_info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(
            "Transaction $id_t transmise au serveur.",
            $last_status_info['message']
        );
    }

    /**
     * Quand on envoie une transaction d'une autorité Passtrans sur la file passtrans, elle est
     * correctement envoyée :
     * 1/ les paramètres du serveur sont corrects (passtrans_server, etc)
     * 2/ sendFileOnUniqueConnection est bien appelé avec un nommage correct
     * 3/ le passage à transmis se fait bien
     * @return void
     */
    public function testSendOneTransactionBonneFilePasstrans2()
    {
        $id_t = $this->createPesAllerToSend("pes_aller_ok.xml");

        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $authoritySQL->query("UPDATE authorities SET helios_use_passtrans = true WHERE id =1");

        $dgfipConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()->getMock();

        $this->dgfipConnectionBuilderMock->expects($this->once())->method('get')
            ->with(
                new DGFiPConnectionConfiguration(
                    'passtrans_server',
                    1982,
                    'passtrans_login',
                    'passtrans_password',
                    DGFiPConnectionMode::PASSTRANS_SFTP,
                    true,
                    'passtrans_sending_destination',
                    'passtrans_response_server_path',
                    'helios_ftp_p_appli'
                )
            )
            ->willReturn($dgfipConnection);
        $dgfipConnection->expects($this->once())->method("sendFileOnUniqueConnection")->with('helios_ftp_dest', 'PES#123#034000#12', "/data/tdt-workspace/helios/sending-tmp//PESALR2_123456789_" . date("ymd") . "_001.xml");
        ob_start();
        $this->heliosEnvoiControler->sendOneTransaction($id_t, true);
        ob_end_clean();
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals(HeliosTransactionsSQL::TRANSMIS, $info['last_status_id']);
        $last_status_info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(
            "Transaction $id_t transmise au serveur. [Passtrans]",
            $last_status_info['message']
        );
    }

    /**
     * @param $filename
     * @return false|mixed
     */
    private function getImportFile($filename): mixed
    {
        $pes_aller = __DIR__ . "/../../../test/PHPUnit/helios/fixtures/{$filename}";
        copy($pes_aller, $this->testStreamUrl . "/helios/" . sha1_file($pes_aller));
        $id_t = $this->heliosController->importFile(8, $pes_aller, "pes_aller.xml");
        return $id_t;
    }

    /**
     * @param mixed $id_t
     * @return void
     */
    private function validate(mixed $id_t): void
    {
        ob_start();
        $this->heliosEnvoiControler->validateOneTransaction($id_t);
        ob_end_clean();
    }
}
