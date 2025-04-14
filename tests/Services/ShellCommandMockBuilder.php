<?php

namespace S2low\Tests\Services;

use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\TestCase;
use S2low\Tests\Services\ValueObject\MockCommandParams;
use S2lowLegacy\Class\ShellCommand;

class ShellCommandMockBuilder
{
    private MockBuilder $mockBuilder;

    public function __construct(TestCase $testCase)
    {
        $this->mockBuilder = $testCase->getMockBuilder(ShellCommand::class);
    }

    /**
     * @param array<MockCommandParams> $commandsToCall
     */
    public function getMock(array $commandsToCall): \PHPUnit\Framework\MockObject\MockObject
    {
        $shellCommand = $this->mockBuilder
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($commandsToCall as $commandToCall) {
            $shellCommand
                ->method($commandToCall->command)
                ->willReturn($commandToCall->return);
        }

        return $shellCommand;
    }
}
