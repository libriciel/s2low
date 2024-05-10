<?php

use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\TransactionSQL;

class TransactionSQLTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    private $transaction_id;
    /** @var TransactionSQL */
    private $transactionSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transaction_id = $this->createTransaction(4);
        $this->transactionSQL = $this->getObjectInstancier()->get(TransactionSQL::class);
    }


    public function testGetAll()
    {
        $this->transactionSQL->setNumero('20170728C');
        $all = $this->transactionSQL->getAll();
        $this->assertEquals($this->transaction_id, $all[0]['transaction_id']);
    }

    //FIX #378
    /*public function testGetAllEmpty(){
        //Ca ne doit pas marché si on ne met pas %xxx% pour chercher une partie
        $this->transactionSQL->setNumero('2017');
        $all = $this->transactionSQL->getAll();
        $this->assertEmpty($all);
    }*/

    public function testGetAllPartial()
    {
        $this->transactionSQL->setNumero('2017%');
        $all = $this->transactionSQL->getAll();
        $this->assertEquals($this->transaction_id, $all[0]['transaction_id']);
    }

    public function testGetAllPartialUndescore()
    {
        $this->transactionSQL->setNumero('2017__28C');
        $all = $this->transactionSQL->getAll();
        $this->assertEquals($this->transaction_id, $all[0]['transaction_id']);
    }
}
