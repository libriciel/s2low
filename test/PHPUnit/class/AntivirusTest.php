<?php

class AntivirusTest extends S2lowSimpleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->getObjectInstancier()->set('antivirus_command', 'ls');
    }

    private function getAntivirus()
    {
        return $this->getObjectInstancier()->get(Antivirus::class);
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
        $this->setExpectedException(
            Exception::class,
            "Erreur 12 lors du scan antivirus de l'archive"
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
        $this->getObjectInstancier()->set(ShellCommand::class, $shellCommand);
    }

    public function testIsAlive()
    {
        $this->assertTrue(
            $this->getAntivirus()->isAlive()
        );
    }

    public function testIsDead()
    {
        $this->setShellCommandReturn(-1);
        $this->setExpectedException(Exception::class, "Problème avec l'antivirus");
        $this->getAntivirus()->isAlive();
    }
}
