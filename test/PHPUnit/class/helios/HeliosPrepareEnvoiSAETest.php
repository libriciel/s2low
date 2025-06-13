<?php

use Monolog\Level;
use S2lowLegacy\Class\helios\HeliosPrepareEnvoiSAE;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\UserSQL;

class HeliosPrepareEnvoiSAETest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;
    use PastellConfigurationTestTrait;

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }

    private function getHeliosPrepareEnvoiSAE(): HeliosPrepareEnvoiSAE
    {
        return new HeliosPrepareEnvoiSAE(
            self::getContainer()->get(PesAllerRetriever::class),
            $this->logger,
            self::getContainer()->get(UserSQL::class),
            self::getContainer()->get(AuthoritySQL::class),
            self::getContainer()->get(HeliosTransactionsSQL::class),
        );
    }

    private function getHeliosTransactionSQL()
    {
        return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }

    public function testSetArchiveEnAttenteEnvoiSEA()
    {
        $this->configurePastell();
        $transaction_id = $this->createTransaction();
        $this->assertTrue(
            $this->getHeliosPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(113, $transaction_id)
        );
        $this->assertEquals(
            HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            $this->getHeliosTransactionSQL()->getLastStatusInfo($transaction_id)['status_id']
        );

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "La transaction $transaction_id passe en attente de transmission au SAE",
                Level::Info
            )
        );
    }

    public function testSetArchiveEnAttenteEnvoiSAEBadState()
    {
        $transaction_id = $this->createTransaction();
        $this->getHeliosTransactionSQL()->updateStatus($transaction_id, 1, "n'importe quoi");
        $this->assertFalse(
            $this->getHeliosPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(113, $transaction_id)
        );
        $this->assertEquals(
            HeliosStatusSQL::POSTE,
            $this->getHeliosTransactionSQL()->getLastStatusInfo($transaction_id)['status_id']
        );
        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Impossible d'archiver une transaction qui n'est pas en état « Information disponible », « acquitté » ou « refusé ».",
                Level::Error
            )
        );
    }

    public function testSetArchiveEnAttenteEnvoiSAEBadTransaction()
    {
        $this->assertFalse(
            $this->getHeliosPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(113, 12)
        );

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "La transaction 12 n'existe pas",
                Level::Error
            )
        );
    }

    public function testSetArchiveEnAttenteEnvoieSAEBadUser()
    {
        $transaction_id = $this->createTransaction();
        $this->assertFalse(
            $this->getHeliosPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(103, $transaction_id)
        );
        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Accès interdit",
                Level::Error
            )
        );
    }

    public function testsetArchiveEnAttenteEnvoiSEAManuellement()
    {
        $this->configurePastell();
        $transaction_id = $this->createTransaction();
        $this->getHeliosTransactionSQL()->updateStatus(
            $transaction_id,
            HeliosStatusSQL::INFORMATION_DISPONIBLE,
            "n'importe quoi"
        );

        $this->getHeliosPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEAManuellement(101, -1);

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "La transaction $transaction_id passe en attente de transmission au SAE",
                Level::Info
            )
        );
    }
}
