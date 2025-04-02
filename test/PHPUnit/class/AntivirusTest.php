<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\ShellCommand;
use S2lowSimpleTestCase;

class AntivirusTest extends S2lowSimpleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->getObjectInstancier()->set('antivirus_command', 'ls');
    }

    private function getAntivirus(): Antivirus
    {
        return $this->getObjectInstancier()->get(Antivirus::class);
    }

    /**
     * @throws Exception
     */
    public function testOK()
    {
        static::assertTrue(
            $this->getAntivirus()->checkFile(__DIR__ . '/fixtures/classification.xml')
        );
    }

    /**
     * @throws Exception
     */
    public function testFailed()
    {
        $this->setShellCommandReturn(12);
        self::expectException(Exception::class);
        self::expectExceptionMessage("Erreur 12 lors du scan antivirus de l'archive");
        $this->getAntivirus()->checkFile(__DIR__ . '/fixtures/classification.xml');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testVirusFound()
    {
        $this->setShellCommandReturn(1);
        static::assertFalse(
            $this->getAntivirus()->checkFile(__DIR__ . '/fixtures/classification.xml')
        );
        static::assertStringContainsString(
            'aaa :  toto FOUND',
            $this->getAntivirus()->getLastError()
        );
    }

    private function setShellCommandReturn($return): void
    {
        $shellCommand = $this->getMockBuilder(ShellCommand::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shellCommand
            ->method('exec')
            ->willReturn($return);
        $shellCommand
            ->method('getLastOutput')
            ->willReturn('/aaa: toto FOUND');
        $this->getObjectInstancier()->set(ShellCommand::class, $shellCommand);
    }
}
