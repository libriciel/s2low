<?php

namespace S2low\Tests\Services;

use Pheanstalk\Job;
use Pheanstalk\PheanstalkInterface;
use S2lowLegacy\Class\BeanstalkdWrapper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpKernel\KernelInterface;

class WorkerCommandKernelTestCase extends KernelTestCase
{
    protected function createBeantstalkdMock($queueName, $jobPayload): BeanstalkdWrapper
    {
        $beanstalkWrapperMock = $this->createMock(BeanstalkdWrapper::class);

        $pheanstalkStub = $this->createStub(PheanstalkInterface::class);

        $pheanstalkStub
            ->expects($this->exactly(11))
            ->method('reserve')
            ->willReturnOnConsecutiveCalls(
                (new Job(
                    rand(1, 100),
                    $jobPayload
                )),
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false
            );

        $pheanstalkStub
            ->method('delete')
            ->willReturn(
                $pheanstalkStub
            );

        $beanstalkWrapperMock
            ->expects($this->once())
            ->method('getQueue')
            ->with($queueName)
            ->willReturn($pheanstalkStub);

        return $beanstalkWrapperMock;
    }

    protected function commandExecute(
        string $commandName,
        KernelInterface $kernel,
        array $commandParams = []
    ): int {
        $application = new Application($kernel);
        $command = $application->find($commandName);
        $commandTester = new CommandTester($command);

        return $commandTester->execute($commandParams);
    }
}
