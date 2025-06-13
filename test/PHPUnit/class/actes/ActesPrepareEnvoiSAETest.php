<?php

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesPrepareEnvoiSAE;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellProperties;

class ActesPrepareEnvoiSAETest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;
    use PastellConfigurationTestTrait;

    /**
     * @return ActesPrepareEnvoiSAE
     */
    private function getActesPrepareEnvoiSAE()
    {
        return self::getContainer()->get(ActesPrepareEnvoiSAE::class);
    }

    /**
     * @throws Exception
     */
    public function testSetArchiveEnAttenteEnvoiSEABadState()
    {
        $transaction_id = $this->createTransaction(1);
        $result = $this->getActesPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(113, $transaction_id);
        $this->assertFalse($result);
        $this->assertEquals(
            "Impossible d'archiver une transaction qui n'est pas en état « Acquittement reçu » ou « Validé ».",
            $this->getActesPrepareEnvoiSAE()->getLastError()
        );
    }

    /**
     * @throws Exception
     */
    public function testSetArchiveEnAttenteEnvoiSAE()
    {
        $this->configurePastell();
        $transaction_id = $this->createTransaction(4);
        $result = $this->getActesPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(113, $transaction_id);
        $this->assertNotFalse($result);
    }

    /**
     * @throws Exception
     */
    public function testSetArchiveEnAttenteEnvoiSAEPastellNotConfigured()
    {
        $transaction_id = $this->createTransaction(4);
        $authoritySQL = $this->getObjectInstancier()->get(AuthoritySQL::class);

        $authoritySQL->updateSAE(1, new PastellProperties());
        $this->assertFalse(
            $this->getActesPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(113, $transaction_id)
        );

        $this->assertEquals(
            "La collectivité n'a pas de Pastell configuré",
            $this->getActesPrepareEnvoiSAE()->getLastError()
        );
    }

    /**
     * @throws Exception
     */
    public function testAccesRefuse()
    {
        $transaction_id = $this->createTransaction(4);
        $result = $this->getActesPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(153, $transaction_id);
        $this->assertFalse($result);
        $this->assertEquals("Accès interdit", $this->getActesPrepareEnvoiSAE()->getLastError());
    }

    public function getActesTransactionsSQL(): \S2lowLegacy\Class\actes\ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }

    public function getObjectInstancier()
    {
        return self::getContainer();
    }
}
