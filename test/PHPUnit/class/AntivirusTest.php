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
        $this->expectException(
            Exception::class
        );
        $this->getAntivirus()->checkArchiveSanity(__DIR__ . "/fixtures/fake_file.txt");
    }

    public function testVirusFound()
    {
        $this->assertFalse(
            $this->getAntivirus()->checkArchiveSanity(__DIR__ . "/fixtures/infected_file.txt")
        );
        $this->assertStringContainsString(
            "L'archive est infectée par un virus.",
            $this->getAntivirus()->getLastError()
        );
    }
}
