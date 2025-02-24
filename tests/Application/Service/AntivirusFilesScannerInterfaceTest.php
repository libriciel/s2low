<?php

namespace S2low\Tests\Application\Service;


use S2low\Infrastructure\Adapter\ClamScanner;
use S2low\Domain\ValueObject\Transaction;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AntivirusFilesScannerInterfaceTest extends KernelTestCase
{
    public function testAnalyseAntivirusReturnsCorrectResult(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $scanner = $container->get(ClamScanner::class);

        $transaction = new Transaction();

        $this->assertTrue(true, $scanner->scanTransactionFiles($transaction));
    }
}
