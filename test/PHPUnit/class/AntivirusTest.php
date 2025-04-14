<?php

use S2low\Tests\Services\ValueObject\MockCommandParams;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\ShellCommand;

class AntivirusTest extends S2lowSimpleTestCase
{
    public function testOK()
    {
        $commandToCall = new MockCommandParams('exec', 0);

        $shellCommandObject = $this->shellCommandMockBuilder->getMock([$commandToCall]);
        $this->container->set(ShellCommand::class, $shellCommandObject);

        $this->assertTrue(
            $this->getAntivirus()->checkArchiveSanity(__DIR__ . "/fixtures/classification.xml")
        );
    }

    private function getAntivirus()
    {
        return $this->container->get(Antivirus::class);
    }

    public function testFailed()
    {
        $commandToCall = new MockCommandParams('exec', 12);

        $shellCommandObject = $this->shellCommandMockBuilder->getMock([$commandToCall]);
        $this->container->set(ShellCommand::class, $shellCommandObject);

        static::expectException(
            Exception::class,
        );
        $this->getAntivirus()->checkArchiveSanity(__DIR__ . "/fixtures/classification.xml");
    }

    public function testVirusFound()
    {
        $commandToCall = new MockCommandParams('exec', 1);

        $shellCommandObject = $this->shellCommandMockBuilder->getMock([$commandToCall]);
        $this->container->set(ShellCommand::class, $shellCommandObject);

        $antivirus = $this->getAntivirus();

        $this->assertFalse(
            $antivirus->checkArchiveSanity(__DIR__ . "/fixtures/classification.xml")
        );
        $this->assertStringContainsString(
            "L'archive est infectée par un virus. Retour de l'antivirus",
            $antivirus->getLastError()
        );
    }
}
