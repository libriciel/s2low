<?php

use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\TransactionSQL;

class TransactionSQLTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    private int $transaction_id;
    private TransactionSQL $transactionSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transaction_id = $this->createTransaction(4);
        $this->transactionSQL = $this->getObjectInstancier()->get(TransactionSQL::class);
    }

    public function testGetAll(): void
    {
        $this->transactionSQL->setNumero('20170728C');
        $all = $this->transactionSQL->getAll();
        static::assertSame($this->transaction_id, $all[0]['transaction_id']);
    }

    public function testGetAllPartial(): void
    {
        $this->transactionSQL->setNumero('2017%');
        $all = $this->transactionSQL->getAll();
        static::assertSame($this->transaction_id, $all[0]['transaction_id']);
    }

    public function testGetAllPartialUnderscore(): void
    {
        $this->transactionSQL->setNumero('2017__28C');
        $all = $this->transactionSQL->getAll();
        static::assertSame($this->transaction_id, $all[0]['transaction_id']);
    }

    public static function setAuthorityAndStatusProvider(): \Generator
    {
        yield [1, 3, 0];
        yield [1, 4, 1];
        yield [1, TransactionSQL::EN_COURS, 1];
        yield [2, 3, 0];
        yield [2, 4, 0];
        yield [2, TransactionSQL::EN_COURS, 0];
    }

    /**
     * @dataProvider setAuthorityAndStatusProvider
     */
    public function testSetAuthorityAndStatus(int $authority, int $status, int $expectedTransactions): void
    {
        $this->transactionSQL->setAuthority($authority);
        $this->transactionSQL->setStatus($status);
        static::assertSame($expectedTransactions, $this->transactionSQL->getNbTransaction());
    }

    protected function getActesTransactionsSQL(): \S2lowLegacy\Class\actes\ActesTransactionsSQL
    {
        return $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
    }

    public function getWorkspace(): \S2lowLegacy\Class\ActesWorkspaceForTests
    {
        // TODO: Implement getWorkspace() method.
    }
}
