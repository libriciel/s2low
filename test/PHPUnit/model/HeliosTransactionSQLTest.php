<?php

declare(strict_types=1);

use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosTransactionSQLTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;
    use PastellConfigurationTestTrait;

    private const FILENAME = "pes_aller.xml";

    /**
     * @var HeliosTransactionsSQL
     */
    private $heliosTransactionSQL;

    private $transaction_id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
        $this->transaction_id = $this->heliosTransactionSQL->create(self::FILENAME, "aaa", 8, 1, 42, 123456789);
        $this->heliosTransactionSQL->updateStatus($this->transaction_id, HeliosTransactionsSQL::POSTE, "test");
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->heliosTransactionSQL;
    }

    public function testGetInfo()
    {
        $info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
        $this->assertEquals(self::FILENAME, $info['filename']);
    }

    public function testUpdateStatus()
    {
        $this->heliosTransactionSQL->updateStatus($this->transaction_id, HeliosTransactionsSQL::ANNULE, "Test");
        $info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
        $this->assertEquals(HeliosTransactionsSQL::ANNULE, $info['last_status_id']);
    }

    public function testGetLastestStatusId()
    {
        $status_id = $this->heliosTransactionSQL->getLatestStatusId($this->transaction_id);
        $this->assertEquals(HeliosTransactionsSQL::POSTE, $status_id);
    }

    public function testSAETransferIdentifier()
    {
        $this->heliosTransactionSQL->setSAETransferIdentifier($this->transaction_id, "azerty");
        $info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
        $this->assertEquals("azerty", $info['sae_transfer_identifier']);
    }

    public function testGetArchiveFormStatusWithSAE()
    {
        $date = "2017-01-01";
        $this->assertEmpty($this->heliosTransactionSQL->getArchiveFromStatusWithSAE(HeliosTransactionsSQL::EN_TRAITEMENT, $date));
    }

    public function testSetArchiveURL()
    {
        $this->heliosTransactionSQL->setArchiveURL($this->transaction_id, "http://www.google.fr");
        $info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
        $this->assertEquals("http://www.google.fr", $info['archive_url']);
    }

    public function testGetTransationToDelete()
    {
        $this->heliosTransactionSQL->updateStatus($this->transaction_id, HeliosTransactionsSQL::ACCEPTE_SAE, "test");
        $transaction_list = $this->heliosTransactionSQL->getTransactionToDelete();
        $this->assertEquals($this->transaction_id, $transaction_list[0]['id']);
    }

    public function testGetTransationsADetruireAjd()   // Les transactions crées aujourd'hui ne peuvent pas être détruites ajd
    {
        $this->heliosTransactionSQL->updateStatus($this->transaction_id, HeliosTransactionsSQL::INFORMATION_DISPONIBLE, "test");
        $transaction_list = $this->heliosTransactionSQL->getTransactionsADetruire(
            (new DateTime())->format("Y-m-d")
        );
        $this->assertEmpty($transaction_list);
    }

    public function testGetTransationsADetruireDemain() // Les transactions créés aujourd'hui seront à détruire demain
    {
        $this->heliosTransactionSQL->updateStatus($this->transaction_id, HeliosTransactionsSQL::INFORMATION_DISPONIBLE, "test");
        $transaction_list = $this->heliosTransactionSQL->getTransactionsADetruire(
            (new DateTime())->modify("+1 day")->format("Y-m-d")
        );
        $this->assertEquals($this->transaction_id, $transaction_list[0]);
    }

    public function testGetTransationsMauvaisEtat() //Les transactions dans un état autre que Erreur ou Info Disponible ne
    {
                                               // doivent pas être détruites
        $this->assertEmpty($this->heliosTransactionSQL->getTransactionsADetruire(
            (new DateTime())->modify("+1 day")->format("Y-m-d")
        ));
    }

    public function testUpdateLastStatusId()
    {
        $this->expectOutputString("{$this->transaction_id} : " . HeliosTransactionsSQL::POSTE . "\n");
        $sql = "UPDATE helios_transactions SET last_status_id=NULL";
        $this->getSQLQuery()->query($sql);
        $this->heliosTransactionSQL->updateLastStatusId();
        $status_id = $this->heliosTransactionSQL->getLatestStatusId($this->transaction_id);
        $this->assertEquals(HeliosTransactionsSQL::POSTE, $status_id);
    }

    public function testSetLastStatusId()
    {
        $this->heliosTransactionSQL->setLastStatusId($this->transaction_id);
        $status_id = $this->heliosTransactionSQL->getLatestStatusId($this->transaction_id);
        $this->assertEquals(HeliosTransactionsSQL::POSTE, $status_id);
    }

    public function testGetIdByStatus()
    {
        $id_list = $this->heliosTransactionSQL->getIdsByStatus(HeliosTransactionsSQL::POSTE);
        $this->assertEquals($this->transaction_id, $id_list[0]);
    }

    public function testGetIdByStatusByAuthorityID()
    {
        $id_list = $this->heliosTransactionSQL->getIdsByStatus(HeliosTransactionsSQL::POSTE, 1);
        $this->assertEquals($this->transaction_id, $id_list[0]);
    }

    public function testGetIdByStatusByAuthorityIDNotExisting()
    {
        $id_list = $this->heliosTransactionSQL->getIdsByStatus(HeliosTransactionsSQL::POSTE, 2);
        $this->assertEmpty($id_list);
    }

    public function testGetIdByNomFic()
    {

        $info  = array(
            'nom_fic' => "pescg291201703030412001",
            'cod_col' => 400,
            'cod_bud' => 01,
            'id_post' => "086016",
        );

        $this->heliosTransactionSQL->setInfoFromPESAller($this->transaction_id, $info);

        $id_list = $this->heliosTransactionSQL->getIdByNomFicAndCodCol("pescg291201703030412001", 400);
        $this->assertEquals(array($this->transaction_id), $id_list);
    }

    public function testNomFicExists()
    {
        $this->heliosTransactionSQL->setInfoFromPESAller(
            $this->transaction_id,
            array(
                'nom_fic' => 'toto',
                'cod_bud' => 42,
                'cod_col' => 123,
                'id_post' => 777
            )
        );
        $this->assertEquals(1, $this->heliosTransactionSQL->nomFicExists("toto", 123));
    }

    public function testSetCompleteName()
    {
        $this->heliosTransactionSQL->setCompleteName($this->transaction_id, "toto");
        $info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
        $this->assertEquals("toto", $info['complete_name']);
    }

    public function testMustSendWarning()
    {
        $this->assertFalse($this->heliosTransactionSQL->mustSendWarning($this->transaction_id));
    }

    public function testSetSendWarning()
    {
        $this->heliosTransactionSQL->setSendWarning($this->transaction_id);
        $this->assertFalse($this->heliosTransactionSQL->mustSendWarning($this->transaction_id));
    }

    public function testDelete()
    {
        $this->heliosTransactionSQL->delete($this->transaction_id);
        $this->assertEmpty($this->heliosTransactionSQL->getInfo($this->transaction_id));
    }

    public function testSetAquitFilename()
    {
        $this->heliosTransactionSQL->setAcquitFilename($this->transaction_id, self::FILENAME);
        $info = $this->heliosTransactionSQL->getInfo($this->transaction_id);
        $this->assertEquals(self::FILENAME, $info['acquit_filename']);
    }

    public function testGetAll()
    {
        $info = $this->heliosTransactionSQL->getAll();
        $this->assertEquals($this->transaction_id, $info[0]['id']);
    }

    public function testGetWorkflow()
    {
        $info = $this->heliosTransactionSQL->getWorkflow($this->transaction_id);
        $this->assertEquals(HeliosTransactionsSQL::POSTE, $info[0]['status_id']);
    }

    public function testIsDuplicate()
    {
        $this->assertTrue((bool)$this->heliosTransactionSQL->isDuplicate("aaa"));
    }

    public function testgetAllTransactionToSendInCloud()
    {
        $info = $this->heliosTransactionSQL->getAllTransactionToSendInCloud();
        $this->assertEquals($this->transaction_id, $info[0]['id']);
    }


    public function testgetAllTransactionToSendInCloudNotAvailable()
    {
        $this->heliosTransactionSQL->setTransactionNotAvailable($this->transaction_id);
        $info = $this->heliosTransactionSQL->getAllTransactionToSendInCloud();
        $this->assertEmpty($info);
        $info = $this->heliosTransactionSQL->getAllTransactionIdToSendInCloud();
        $this->assertEmpty($info);
    }

    public function testGetTransactionToArchive()
    {
        $this->configurePastell();
        $transaction_id = $this->createTransaction();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id,
            HeliosStatusSQL::INFORMATION_DISPONIBLE,
            "test"
        );
        $this->assertEquals(
            [$transaction_id],
            $this->heliosTransactionSQL->getTransactionToPrepareToSAE(-1)
        );
    }

    public function testGetTransactionToArchiveDoubleInformationDisponible()
    {
        $this->configurePastell();
        $transaction_id = $this->createTransaction();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id,
            HeliosStatusSQL::INFORMATION_DISPONIBLE,
            "test"
        );
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id,
            HeliosStatusSQL::INFORMATION_DISPONIBLE,
            "test doublon information disponible"
        );
        $this->assertEquals(
            [$transaction_id],
            $this->heliosTransactionSQL->getTransactionToPrepareToSAE(-1)
        );
    }

    public function testGetTransactionToSendToArchive()
    {
        $this->configurePastell();
        $transaction_id = $this->createTransaction();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id,
            HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            "test"
        );
        $this->assertEquals(
            [$transaction_id],
            $this->heliosTransactionSQL->getTransactionsToSendToSAE(100)
        );
    }

    public function testGetTransactionToSendToArchiveWithLimit()
    {
        $this->configurePastell();
        $transaction_id1 = $this->createTransaction();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id1,
            HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            "test"
        );
        $transaction_id2 = $this->createTransaction();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id2,
            HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            "test"
        );
        $this->assertEquals(
            [$transaction_id1],
            $this->heliosTransactionSQL->getTransactionsToSendToSAE(1)
        );
        $this->assertEquals(
            [$transaction_id1,$transaction_id2],
            $this->heliosTransactionSQL->getTransactionsToSendToSAE()
        );
    }

    public function testGetTransactionToSendToArchiveWithAlreadyEnoughWaiting()
    {
        $this->configurePastell();
        $transaction_id1 = $this->createTransaction();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id1,
            HeliosTransactionsSQL::ENVOYER_AU_SAE,
            "test"
        );
        $transaction_id2 = $this->createTransaction();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id2,
            HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            "test"
        );
        $this->assertEquals(
            [],
            $this->heliosTransactionSQL->getTransactionsToSendToSAE(1)
        );
        $this->assertEquals(
            [$transaction_id2],
            $this->heliosTransactionSQL->getTransactionsToSendToSAE()
        );
    }

    public function testGetTransactionToSendToArchiveWithTwoAuthorities()
    {
        $this->configurePastell();
        $this->configurePastell(2);

        $transaction_id1_1 = $this->createTransaction();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id1_1,
            HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            "test"
        );
        $transaction_id1_2 = $this->createTransaction();
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id1_2,
            HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            "test"
        );
        $transaction_id2_1 = $this->createTransaction(2);
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id2_1,
            HeliosTransactionsSQL::ENVOYER_AU_SAE,
            "test"
        );
        $transaction_id2_2 = $this->createTransaction(2);
        $this->heliosTransactionSQL->updateStatus(
            $transaction_id2_2,
            HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            "test"
        );
        $this->assertEquals(
            [$transaction_id1_1],
            $this->heliosTransactionSQL->getTransactionsToSendToSAE(1)
        );
        $this->assertEquals(
            [$transaction_id1_1,$transaction_id1_2,$transaction_id2_2],
            $this->heliosTransactionSQL->getTransactionsToSendToSAE()
        );
    }

    public function testSetPesAcquitAvailable()
    {
        $transaction_id = $this->createTransaction();
        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
        $heliosTransactionsSQL->setPesAcquitAvailable($transaction_id, true);
        $this->assertTrue($heliosTransactionsSQL->isPesAcquitAvailable($transaction_id));
    }

    public function testgetNonAcquitteWithGateway()
    {
        $this->heliosTransactionSQL->updateStatus($this->transaction_id, 3, "test");
        $this->assertEquals(
            $this->heliosTransactionSQL->getNonAcquitteWithPasstransStatus(
                false,
                "2030-12-12"
            )[0]['id'],
            $this->transaction_id
        );
    }

    public function testgetNonAcquitteWithPasstrans()
    {
        $this->assertEquals(
            $this->heliosTransactionSQL->getNonAcquitteWithPasstransStatus(
                true,
                date("Y-m-d", strtotime('tomorrow'))
            ),
            []
        );
    }

    public function testgetListByStatusAndAuthority(): void
    {
        static::assertSame(
            $this->heliosTransactionSQL->getListByStatusAndAuthority(
                HeliosTransactionsSQL::POSTE,
                1,
                0,
                100
            ),
            [['id' => $this->transaction_id]]
        );
    }
}
