<?php

use S2low\Services\Helios\HeliosEnvoiControler;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Controller\HeliosController;
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

        mkdir($this->testStreamUrl . "/helios");
        $this->getObjectInstancier()->set("helios_files_upload_root", $this->testStreamUrl . "/helios/");
        $this->heliosController = new HeliosController($this->getObjectInstancier());
        $this->heliosEnvoiControler = new HeliosEnvoiControler(
            $this->getContainer()->get(\S2lowLegacy\Lib\SQLQuery::class),
            $this->getContainer()->get(\S2lowLegacy\Class\helios\PesAllerRetriever::class),
            "helios_files_upload_root",
            $this->getContainer()->get(\S2lowLegacy\Class\Antivirus::class),
            $this->getContainer()->get(\S2lowLegacy\Class\WorkerScript::class),
            new \S2low\Services\Helios\FTPHeliosSenderFactory(
                $this->getContainer()->get(\S2low\Services\Helios\HeliosConnectionBuilder::class),
                "helios_ftp_appli"
            ),
            $this->getContainer()->get(\S2low\Services\MailActesNotifications\MailerSymfonyFactory::class),
            new \S2low\Services\Helios\HeliosConnectionsConfigurationManager(
                "helios_ftp_server",
                "helios_ftp_port",
                "helios_ftp_login",
                "helios_ftp_password",
                "helios_ftp_response_server_path",
                "helios_sending_destination",
                "helios_ftp_passtrans_mode",
                true,
                "helios_ftp_server2",
                "helios_ftp_port2",
                "helios_ftp_login2",
                "helios_ftp_password2",
                "helios_ftp_passtrans_mode2",
                true,
                "helios_ftp_response_server_path2",
                "helios_sending_destination2",
                new \S2low\Services\Helios\FTPConnection\FullConfigurationBuilder()
            )
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
        $pes_aller = __DIR__ . "/../../../test/PHPUnit/helios/fixtures/{$filename}";
        copy($pes_aller, $this->testStreamUrl . "/helios/" . sha1_file($pes_aller));
        $id_t = $this->heliosController->importFile(8, $pes_aller, "pes_aller.xml");
        ob_start();
        $this->heliosEnvoiControler->validateOneTransaction($id_t);
        $this->last_string = ob_get_contents();
        ob_end_clean();
        return $id_t;
    }

    /**
     * @throws Exception
     */
    public function testNotInIso8859()
    {
        $id_t = $this->validatePesAller("pes_aller_utf8.xml");

        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals(HeliosStatusSQL::ERREUR, $info['last_status_id']);
        $last_status_info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals("Transaction $id_t : ce fichier n'est pas encodé en ISO-8859-1", $last_status_info['message']);
    }

    public function testCodCollTropLong()
    {
        $id_t = $this->validatePesAller("pes_aller_CodColTropLong.xml");

        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getInfo($id_t);
        $this->assertEquals(HeliosStatusSQL::ERREUR, $info['last_status_id']);
        $last_status_info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals("Transaction $id_t : Le CodCol est trop long", $last_status_info['message']);
    }

    /**
     * @throws Exception
     */
    public function testAccentNomFic()
    {
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
        $id_t = $this->validatePesAller("/../../class/fixtures/pes_no_id.xml");
        $heliosTransaction = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransaction->getLastStatusInfo($id_t);
        $this->assertEquals(HeliosTransactionsSQL::ATTENTE, $info['status_id']);
        $this->assertMatchesRegularExpression("#Transaction $id_t dans la file d'attente#", $info['message']);
    }

    public function testDejaSigneBadSignature()
    {
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
}
