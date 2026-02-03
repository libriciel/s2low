<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Helios;

use Exception;
use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnection;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionMode;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager;
use S2low\Services\Helios\HeliosEnvoiControler;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\helios\FichierCompteur;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\helios\HeliosTransmissionWindowsSQL;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\VerifyPemCertificateFactory;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Lib\HeliosNamesGenerator;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Lib\PesAllerReader;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosEnvoiControlerTest extends S2lowIntegrationTestCase
{
    private string $testStreamUrl;
    private string $counterDir;
    private HeliosController $heliosController;
    private HeliosEnvoiControler $envoiControler;
    private TmpFolder $tmpFolder;
    private MockObject|WorkerScript $workerScript;
    private DGFiPConnectionBuilder|MockObject $connectBuilder;
    private AuthoritySQL $authoritySQL;

    private AuthoritySiretSQL $authoritySiretSQL;
    private HeliosTransactionsSQL $transactionsSQL;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpFolder = new TmpFolder();
        $this->workerScript = $this->getMockBuilder(WorkerScript::class)
            ->disableOriginalConstructor()->getMock();
        $this->connectBuilder = $this->getMockBuilder(DGFiPConnectionBuilder::class)
            ->disableOriginalConstructor()->getMock();
        $this->authoritySQL = static::getContainer()->get(AuthoritySQL::class);
        $this->authoritySiretSQL = static::getContainer()->get(AuthoritySiretSQL::class);
        $this->transactionsSQL = static::getContainer()->get(HeliosTransactionsSQL::class);

        $this->testStreamUrl = $this->tmpFolder->create();
        $this->counterDir = $this->tmpFolder->create();
        $counterFile = fopen($this->counterDir . '/counter.txt', 'w');
        fwrite($counterFile, '000');

        mkdir($this->testStreamUrl . '/helios');
        $this->heliosController = self::getContainer()->get(HeliosController::class);
        $this->envoiControler = $this->getHeliosEnvoiController();
    }

    protected function tearDown(): void
    {
        $this->tmpFolder->delete($this->testStreamUrl);
        $this->tmpFolder->delete($this->counterDir);
        parent::tearDown();
    }

    /**
     * @param $filename
     * @return int
     * @throws Exception
     */
    private function validatePesAller($filename): int
    {
        $id_transaction = $this->getImportFile($filename);
        $this->validate($id_transaction);
        return $id_transaction;
    }

    /**
     * @param mixed $id_transaction
     * @return void
     * @throws Exception
     */
    private function validate(mixed $id_transaction): void
    {
        ob_start();
        $this->envoiControler->validateOneTransaction($id_transaction);
        ob_end_clean();
    }

    /**
     * @param $filename
     * @return false|mixed
     */
    private function getImportFile($filename): mixed
    {
        $pes_aller = $this->projectDir . "/test/PHPUnit/helios/fixtures/$filename";
        $pesAllerPourTest = $this->testStreamUrl . '/helios/' . sha1_file($pes_aller);
        copy($pes_aller, $pesAllerPourTest);
        return $this->heliosController->importFile(13, $pes_aller, 'pes_aller.xml');
    }

    /**
     * @throws Exception
     */
    public function testValidateAllTransactions()
    {
        $this->validatePesAller('pes_aller_ok.xml');
        $siret_list = $this->authoritySiretSQL->siretList(1);
        static::assertEquals("12345678912345", $siret_list[0]['siret']);
    }

    /**
     * @throws Exception
     */
    public function testNotInIso8859()
    {
        $this->workerScript->expects(static::never())->method('putJobByQueueName');
        $id_transaction = $this->validatePesAller('pes_aller_utf8.xml');

        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::ERREUR, $info_transaction['last_status_id']);
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);

        static::assertEquals(
            "Transaction $id_transaction : ce fichier n'est pas encodé en ISO-8859-1",
            $last_status_info['message']
        );
    }

    /**
     * @throws Exception
     */
    public function testCodCollTropLong(): void
    {
        $this->workerScript->expects(static::never())->method('putJobByQueueName');
        $id_transaction = $this->validatePesAller('pes_aller_CodColTropLong.xml');

        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::ERREUR, $info_transaction['last_status_id']);
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals("Transaction $id_transaction : Le CodCol est trop long", $last_status_info['message']);
    }

    public function testIdCollTropLong(): void
    {
        $this->workerScript->expects(static::never())->method('putJobByQueueName');
        $id_transaction = $this->validatePesAller('pes_aller_IdColl_trop_long.xml');

        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::ERREUR, $info_transaction['last_status_id']);
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals("Transaction $id_transaction : La balise IdCol est trop longue", $last_status_info['message']);
    }

    /**
     * @throws Exception
     */
    public function testPasDeNomFic()
    {
        $this->workerScript->expects(static::never())->method('putJobByQueueName');
        $id_transaction = $this->validatePesAller('pes_aller_PasDeNomFic.xml');

        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::ERREUR, $info_transaction['last_status_id']);
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(
            "Transaction $id_transaction : La balise Enveloppe/Parametre/NomFic n'est pas présente ou est vide",
            $last_status_info['message']
        );
    }

    /**
     * @throws Exception
     */
    public function testAccentNomFic()
    {
        $this->workerScript->expects(static::once())->method('putJobByQueueName');
        $id_transaction = $this->validatePesAller('PESALR2_accent_dans_nomfic.xml');

        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);

        static::assertEquals(
            'PESALR220001861200016Trésorerie_de_M20141205152530.xml',
            $info_transaction['xml_nomfic']
        );

        $siret_list = $this->authoritySiretSQL->siretList(1);
        static::assertEquals('12345678912345', $siret_list[0]['siret']);
    }

    /**
     * @throws Exception
     */
    public function testRetrieveAllPesInfo()
    {
        $id_transaction = $this->validatePesAller('pes_aller_ok.xml');
        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals('03f432a4f6d35110bf309fb525eb61f7', $info_transaction['xml_nomfic']);
        static::assertEquals('123', $info_transaction['xml_cod_col']);
        static::assertEquals('12', $info_transaction['xml_cod_bud']);
        static::assertEquals('034000', $info_transaction['xml_id_post']);
    }

    /**
     * @throws Exception
     */
    public function testChangedPesAller()
    {
        $this->workerScript->expects(static::never())->method('putJobByQueueName');
        $pes_aller = __DIR__ . '/../../../test/PHPUnit/helios/fixtures/pes_aller_ok.xml';
        copy($pes_aller, $this->testStreamUrl . '/helios/' . sha1_file($pes_aller));
        $transaction_id = $this->heliosController->importFile(13, $pes_aller, 'pes_aller.xml');
        $pes_aller_change = __DIR__ . '/../../../test/PHPUnit/helios/fixtures/pes_aller.xml';
        copy($pes_aller_change, $this->testStreamUrl . '/helios/' . sha1_file($pes_aller));
        ob_start();
        $this->envoiControler->validateOneTransaction($transaction_id);
        ob_end_clean();

        $info_transaction = $this->transactionsSQL->getLastStatusInfo($transaction_id);
        static::assertEquals(-1, $info_transaction['status_id']);
        static::assertMatchesRegularExpression(
            '#Le fichier a été modifé depuis son postage sur la plateforme#',
            $info_transaction['message']
        );
    }

    /**
     * @throws Exception
     */
    public function testSigneNoID()
    {
        $id_transaction = $this->getImportFile('/../../class/fixtures/pes_no_id.xml');
        $this->workerScript->expects(static::once())->method('putJobByQueueName')
            ->with('helios-envoi', "$id_transaction");
        $this->validate($id_transaction);
        $info_transaction = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::ATTENTE, $info_transaction['status_id']);
        static::assertMatchesRegularExpression(
            "#Transaction $id_transaction dans la file d'attente#",
            $info_transaction['message']
        );
    }

    /**
     * @throws Exception
     */
    public function testSigneNoIDPasstrans()
    {
        $this->authoritySQL->query('UPDATE authorities SET helios_use_passtrans = true WHERE id =1');
        $id_transaction = $this->getImportFile('/../../class/fixtures/pes_no_id.xml');
        $this->workerScript->expects(static::once())->method('putJobByQueueName')
            ->with('helios-envoi-passtrans', "$id_transaction");
        $this->validate($id_transaction);
        $info_transaction = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::ATTENTE, $info_transaction['status_id']);
        static::assertMatchesRegularExpression(
            "#Transaction $id_transaction dans la file d'attente#",
            $info_transaction['message']
        );
    }

    /**
     * @throws Exception
     */
    public function testDejaSigneBadSignature()
    {
        $this->workerScript->expects(static::never())->method('putJobByQueueName');
        $id_transaction = $this->validatePesAller('/../../lib/fixtures/HELIOS_SIMU_ALR2_bad_signature.xml');
        $info_transaction = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(HeliosStatusSQL::ERREUR, $info_transaction['status_id']);
        static::assertMatchesRegularExpression('#La signature du fichier est invalide#', $info_transaction['message']);
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
    private function sendSamePESAllerFailed(): void
    {
        $this->validatePesAller('pes_aller_ok.xml');
        $id_transaction = $this->validatePesAller('pes_aller_ok.xml');

        $info_transaction = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(-1, $info_transaction['status_id']);
        static::assertMatchesRegularExpression(
            '#ce fichier existe déjà sur la plateforme#',
            $info_transaction['message']
        );
    }


    /**
     * @throws Exception
     */
    public function testSendSamePESAllerDoNotVerify()
    {
        $this->workerScript->expects(static::exactly(2))->method('putJobByQueueName');
        $this->envoiControler->setDoNotVerifyNomFicUnicity(true);
        $this->authoritySQL->updateDoNotVerifyNomFicUnicity(1, true);
        $this->validatePesAller('pes_aller_ok.xml');
        $id_transaction = $this->validatePesAller('pes_aller_ok.xml');
        $info_transaction = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(2, $info_transaction['status_id']);
    }

    /**
     * @throws Exception
     */
    public function testSendSamePESAllerDoNotVerifyOnlyConst()
    {
        $this->envoiControler->setDoNotVerifyNomFicUnicity(true);
        $this->sendSamePESAllerFailed();
    }

    /**
     * @throws Exception
     */
    public function testSendSamePESAllerDoNotVerifyOnlyAuthority()
    {
        $this->authoritySQL->updateDoNotVerifyNomFicUnicity(1, true);
        $this->sendSamePESAllerFailed();
    }

    /**
     * @throws Exception
     */
    public function testWhenPESAllerIsEmpty()
    {
        $this->workerScript->expects(static::never())->method('putJobByQueueName');
        $id_transaction = $this->validatePesAller('pes_aller_empty.xml');
        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(-1, $info_transaction['last_status_id']);
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(
            "Transaction $id_transaction : ce fichier ne contient ni bordereau, ni PJ, ni marché",
            $last_status_info['message']
        );
    }

    /**
     * @throws Exception
     */
    private function createPesAllerToSend($filename)
    {
        $id_transaction = $this->getImportFile($filename);
        $this->validate($id_transaction);
        return $id_transaction;
    }

    /**
     * Quand on envoie une transaction d'une autorité non Passtrans sur la file passtrans,
     * 1/ elle se retrouve en erreur
     * 2/ l'envoi ne se fait pas ( dgfipConnectionBuilderMock non appelé )
     * @return void
     * @throws Exception
     */
    public function testSendOneTransactionMauvaiseFilePasstrans()
    {
        $this->connectBuilder->expects(static::never())->method('get');

        $id_transaction = $this->createPesAllerToSend('pes_aller_ok.xml');
        ob_start();
        $this->envoiControler->sendOneTransaction($id_transaction, true);
        ob_end_clean();
        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::ERREUR, $info_transaction['last_status_id']);
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);

        static::assertEquals(
            "Transaction $id_transaction : la transaction a été aiguillée sur la mauvaise file passtrans",
            $last_status_info['message']
        );
    }

    /**
     * Quand on envoie une transaction d'une autorité Passtrans sur la file non passtrans,
     * 1/ elle se retrouve en erreur aussi
     * 2/ l'envoi ne se fait pas ( dgfipConnectionBuilderMock non appelé )
     * @return void
     * @throws Exception
     */
    public function testSendOneTransactionMauvaiseFilePasstrans2()
    {
        $this->connectBuilder->expects(static::never())->method('get');

        $this->authoritySQL->query('UPDATE authorities SET helios_use_passtrans = true WHERE id =1');

        $id_transaction = $this->createPesAllerToSend('pes_aller_ok.xml');
        ob_start();
        $this->envoiControler->sendOneTransaction($id_transaction, false);
        ob_end_clean();
        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::ERREUR, $info_transaction['last_status_id']);
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);

        static::assertEquals(
            "Transaction $id_transaction : la transaction a été aiguillée sur la mauvaise file passtrans",
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
     * @throws \Exception
     */
    public function testSendOneTransactionBonneFilePasstrans()
    {
        $id_transaction = $this->createPesAllerToSend('pes_aller_ok.xml');

        $dgfipConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()->getMock();

        $this->connectBuilder->expects(static::once())->method('get')
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
        $dgfipConnection->expects(static::once())->method('sendFileOnUniqueConnection')
            ->with(
                'helios_ftp_dest',
                'PES#123#034000#12',
                '/data/tdt-workspace/helios/sending-tmp//PESALR2_123456789_' . date('ymd') . '_001.xml'
            );
        ob_start();
        $this->envoiControler->sendOneTransaction($id_transaction, false);
        ob_end_clean();
        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::TRANSMIS, $info_transaction['last_status_id']);
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(
            "Transaction $id_transaction transmise au serveur.",
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
     * @throws Exception
     */
    public function testSendOneTransactionBonneFilePasstrans2()
    {
        $id_transaction = $this->createPesAllerToSend('pes_aller_ok.xml');

        $this->authoritySQL->query('UPDATE authorities SET helios_use_passtrans = true WHERE id =1');

        $dgfipConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()->getMock();

        $this->connectBuilder->expects(static::once())->method('get')
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
        $dgfipConnection->expects(static::once())
            ->method('sendFileOnUniqueConnection')
            ->with(
                'helios_ftp_dest',
                'PES#123#034000#12',
                '/data/tdt-workspace/helios/sending-tmp//PESALR2_123456789_' . date('ymd') . '_001.xml'
            );
        ob_start();
        $this->envoiControler->sendOneTransaction($id_transaction, true);
        ob_end_clean();
        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(HeliosTransactionsSQL::TRANSMIS, $info_transaction['last_status_id']);
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(
            "Transaction $id_transaction transmise au serveur. [Passtrans]",
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
     * @throws Exception
     */
    public function testSendOnePesAcquitRetour()
    {
        $id_transaction = $this->createPesAllerToSend('PES_ACQUIT_RETOUR.xml');
        $this->envoiControler->sendOneTransaction($id_transaction, false);

        $info_transaction = $this->transactionsSQL->getInfo($id_transaction);
        static::assertEquals(
            HeliosTransactionsSQL::TRANSMIS_SANS_ACK,
            $info_transaction['last_status_id']
        );
        $last_status_info = $this->transactionsSQL->getLastStatusInfo($id_transaction);
        static::assertEquals(
            "Transaction $id_transaction transmise au serveur.",
            $last_status_info['message']
        );
    }

    private function getHeliosEnvoiController()
    {
        $pesAllerRetriever = new PesAllerRetriever(
            $this->testStreamUrl . '/helios/',
            self::getContainer()->get(OpenStackSwiftWrapper::class),
            self::getContainer()->get(LoggerInterface::class)
        );

        return new HeliosEnvoiControler(
            static::getContainer()->get(AuthoritySiretSQL::class),
            static::getContainer()->get(HeliosTransactionsSQL::class),
            static::getContainer()->get(AuthoritySQL::class),
            static::getContainer()->get(HeliosTransmissionWindowsSQL::class),
            $pesAllerRetriever,
            static::getContainer()->get(Antivirus::class),
            $this->workerScript,
            static::getContainer()->get(MailerSymfonyFactory::class),
            new DGFiPConnectionsManager(
                new DGFiPConnectionConfiguration(   //Passtrans Connection
                    'passtrans_server',
                    1982,
                    'passtrans_login',
                    'passtrans_password',
                    DGFiPConnectionMode::PASSTRANS_SFTP,
                    true,
                    'passtrans_sending_destination',
                    'passtrans_response_server_path',
                    'helios_ftp_p_appli'
                ),
                new DGFiPConnectionConfiguration(   //Gateway Connection
                    'std_server',
                    1982,
                    'std_login',
                    'std_password',
                    DGFiPConnectionMode::GATEWAY,
                    true,
                    'std_sending_destination',
                    'std_response_server_path',
                    'helios_ftp_p_appli'
                ),
                $this->connectBuilder
            ),
            new FichierCompteur($this->counterDir . '/counter.txt'),
            static::getContainer()->get(LoggerInterface::class),
            new PesAllerReader(),
            new HeliosNamesGenerator(),
            new VerifyPemCertificateFactory()
        );
    }
}
