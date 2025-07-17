<?php

use S2lowLegacy\Class\helios\HeliosTransactionsListe;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosTransactionsListeTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return self::getContainer()->get(HeliosTransactionsSQL::class);
    }
    public function heliosTransactionListe(): HeliosTransactionsListe
    {
        return self::getContainer()->get(HeliosTransactionsListe::class);
    }

    /**
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $this->createTransaction();
        $all = $this->heliosTransactionListe()->getAll();
        static::assertSame('toto.txt', $all[0]['filename']);
    }

    public static function statusAndAuthorityProvider(): \Generator
    {
        yield [3,1,0];
        yield [4,1,1];
        yield [4,102,0];
    }

    /**
     * @dataProvider statusAndAuthorityProvider
     * @throws Exception
     */
    public function testGetAllWithStatusAndAuthority(int $status, int $authority, int $expectedCount): void
    {
        $this->createTransaction();
        $this->heliosTransactionListe()->setStatus($status);
        $this->heliosTransactionListe()->setAuthority($authority);
        static::assertCount(
            $expectedCount,
            $this->heliosTransactionListe()->getAll()
        );
    }
}
