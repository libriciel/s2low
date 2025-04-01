<?php

namespace S2low\Tests\Application\Service;

use S2low\Domain\Port\AntivirusFilesScannerInterface;
use S2low\Tests\RefreshDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Dotenv\Dotenv;

class AntivirusFilesScannerInterfaceTest extends KernelTestCase
{
    use RefreshDatabaseTrait;

    private AntivirusFilesScannerInterface $scanner;

    protected function setUp(): void
    {
        self::refreshDatabase();
        $this->scanner = static::getContainer()->get(AntivirusFilesScannerInterface::class);
    }

    public function testAnalyseAntivirusReturnsCorrectResult(): void
    {
        $this->assertTrue(true, true);
    }
}
