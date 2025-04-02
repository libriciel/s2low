<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use PHPUnit\Framework\TestCase;
use S2low\Exceptions\AntivirusCommandException;
use S2low\Exceptions\InfectedFileException;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\ShellCommand;
use Symfony\Component\Filesystem\Filesystem;

class AntivirusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->shellCommand = $this->getMockBuilder(ShellCommand::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->antivirus = new Antivirus(
            $this->shellCommand,
            'ls',
            new Filesystem()
        );
    }

    /**
     * @throws Exception
     */
    public function testOK()
    {
        self::expectNotToPerformAssertions();
        $this->setShellCommandReturn(0);
        $this->antivirus->checkFile(__DIR__ . '/fixtures/classification.xml');
    }

    /**
     * @throws Exception
     */
    public function testFailed()
    {
        $this->setShellCommandReturn(12);
        self::expectException(AntivirusCommandException::class);
        self::expectExceptionMessage("Erreur 12 lors du scan antivirus de l'archive");
        $this->antivirus->checkFile(__DIR__ . '/fixtures/classification.xml');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testVirusFound()
    {
        $this->setShellCommandReturn(1);
        self::expectException(InfectedFileException::class);
        self::expectExceptionMessageMatches("/aaa :  toto FOUND/");
        $this->antivirus->checkFile(__DIR__ . '/fixtures/classification.xml');
    }

    private function setShellCommandReturn($return): void
    {
        $this->shellCommand
            ->method('exec')
            ->willReturn($return);
        $this->shellCommand
            ->method('getLastOutput')
            ->willReturn('/aaa: toto FOUND');
    }
}
