<?php

class HeliosTransactionsListeTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    public function testGetAll()
    {
        $this->createTransaction();
        $heliosTransactionsListe  = $this->getObjectInstancier()->get(HeliosTransactionsListe::class);
        $all = $heliosTransactionsListe->getAll();
        $this->assertEquals('toto.txt', $all[0]['filename']);
    }
}
