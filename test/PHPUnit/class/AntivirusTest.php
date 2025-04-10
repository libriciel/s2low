<?php

use Psr\Container\ContainerInterface;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\ShellCommand;

class AntivirusTest extends S2lowSimpleTestCase
{
    public ContainerInterface $container;
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = static::getContainer();
    }

    private function getAntivirus()
    {
        return $this->container->get(Antivirus::class);
    }

    /**
     * @throws Exception
     */
    public function testOK()
    {
        $this->setShellCommandReturn(0);
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

    private function setShellCommandReturn($return): void
    {
        if (!$this->container->has(ShellCommand::class)) {
            throw new \LogicException('ShellCommand service not found in container.');
        }

        $shellCommand = $this->getMockBuilder(ShellCommand::class)
            ->disableOriginalConstructor()
            ->getMock();

        $shellCommand
            ->method('exec')
            ->willReturn($return);

        $shellCommand
            ->method('getLastOutput')
            ->willReturn('/aaa: toto FOUND');

        $this->container->set(ShellCommand::class, $shellCommand);
    }
}
