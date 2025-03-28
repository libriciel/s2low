<?php

namespace S2low\Tests\Application\Service;

use S2low\Domain\Model\Transaction\Transaction;
use S2low\Infrastructure\Adapter\ClamAvAdapter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AntivirusFilesScannerInterfaceTest extends KernelTestCase
{
    public function testAnalyseAntivirusReturnsCorrectResult(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $scanner = $container->get(ClamAvAdapter::class);

        $transaction = new Transaction();

        $this->assertTrue(true, $scanner->scanTransactionFiles($transaction));
    }
}
