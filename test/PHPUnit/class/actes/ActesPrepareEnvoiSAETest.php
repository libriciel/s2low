<?php

class ActesPrepareEnvoiSAETest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;
    use PastellConfigurationTestTrait;

    /**
     * @return ActesPrepareEnvoiSAE
     */
    private function getActesPrepareEnvoiSAE()
    {
        return $this->getObjectInstancier()->get(ActesPrepareEnvoiSAE::class);
    }

    /**
     * @throws Exception
     */
    public function testSetArchiveEnAttenteEnvoiSEABadState()
    {
        $transaction_id = $this->createTransaction(1);
        $result = $this->getActesPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(1, $transaction_id);
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
        $result = $this->getActesPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(1, $transaction_id);
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
            $this->getActesPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(1, $transaction_id)
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
        $result = $this->getActesPrepareEnvoiSAE()->setArchiveEnAttenteEnvoiSEA(5, $transaction_id);
        $this->assertFalse($result);
        $this->assertEquals("Accès interdit", $this->getActesPrepareEnvoiSAE()->getLastError());
    }
}
