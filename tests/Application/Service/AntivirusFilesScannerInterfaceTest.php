<?php

namespace S2low\Tests\Application\Service;

use S2low\Domain\Exception\VirusDetectedException;
use S2low\Domain\Port\AntivirusFilesScannerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AntivirusFilesScannerInterfaceTest extends KernelTestCase
{
    const INFECTED_PATH_FILE = __DIR__ . '/../../../src/DataFixtures/fixturesFiles/infected_file.txt';
    const SAFE_PATH_FILE = __DIR__ . '/../../../src/DataFixtures/fixturesFiles/PDFTest.pdf';

    private AntivirusFilesScannerInterface $scanner;

    protected function setUp(): void
    {
        $this->scanner = static::getContainer()->get(AntivirusFilesScannerInterface::class);
    }

    public function testAnalyseAntivirusReturnsInfected(): void
    {
        self::expectException(VirusDetectedException::class);
        $this->scanner->scan(self::INFECTED_PATH_FILE);
        $this->assertTrue(true, true);
    }

    public function testAnalyseAntivirusReturnsSafe(): void
    {
        self::expectNotToPerformAssertions();
        $this->scanner->scan(self::SAFE_PATH_FILE);
    }
}
