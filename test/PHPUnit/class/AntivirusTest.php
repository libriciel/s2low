<?php

use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\ShellCommand;

class AntivirusTest extends S2lowSimpleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    private function getAntivirus()
    {
        return static::getContainer()->get(Antivirus::class);
    }

    /**
     * @throws Exception
     */
    public function testOK()
    {
        $this->assertTrue(
            $this->getAntivirus()->checkArchiveSanity(__DIR__ . "/fixtures/classification.xml")
        );
    }

    /**
     * @throws Exception
     */
    public function testFailed()
    {
        $this->setShellCommandReturn(12);
        static::expectException(
            Exception::class,
        );
        $this->getAntivirus()->checkArchiveSanity(__DIR__ . "/fixtures/classification.xml");
    }

    public function testVirusFound()
    {
        $this->setShellCommandReturn(1);
        $this->assertFalse(
            $this->getAntivirus()->checkArchiveSanity(__DIR__ . "/fixtures/classification.xml")
        );
        $this->assertStringContainsString(
            "aaa :  toto FOUND",
            $this->getAntivirus()->getLastError()
        );
    }

    private function setShellCommandReturn($return)
    {
        $shellCommand = $this->getMockBuilder(ShellCommand::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shellCommand
            ->method('exec')
            ->willReturn($return);
        $shellCommand
            ->method('getLastOutput')
            ->willReturn("/aaa: toto FOUND");
        static::getContainer()->set(ShellCommand::class, $shellCommand);
    }
}
