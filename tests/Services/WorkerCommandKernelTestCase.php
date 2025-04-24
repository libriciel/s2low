<?php

namespace S2low\Tests\Services;

use Pheanstalk\Job;
use Pheanstalk\PheanstalkInterface;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Class\RedisMutexWrapper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class WorkerCommandKernelTestCase extends KernelTestCase
{
    protected function simuleBeanstalkdAndRedis($beanstalkdPayload): void
    {
        $redisWrapperMock = $this->createRedisWrapperMock();
        $beanstalkWrapperMock = $this->createBeantstalkdMock(
            $beanstalkdPayload
        );

        self::getContainer()->set(RedisMutexWrapper::class, $redisWrapperMock);
        self::getContainer()->set(BeanstalkdWrapper::class, $beanstalkWrapperMock);
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

    private function createBeantstalkdMock(
        mixed $jobPayload
    ): BeanstalkdWrapper {
        $beanstalkWrapperMock = $this->createMock(BeanstalkdWrapper::class);

        $pheanstalkStub = $this->createPheanstalkStub($jobPayload);

        $beanstalkWrapperMock
            ->expects($this->once())
            ->method('getQueue')
            ->willReturn($pheanstalkStub);

        return $beanstalkWrapperMock;
    }

    private function createPheanstalkStub(
        mixed $jobPayload
    ): PheanstalkInterface {
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

        return $pheanstalkStub;
    }

    private function createRedisWrapperMock(): RedisMutexWrapper
    {
        $redisMock = $this->createMock(RedisMutexWrapper::class);
        $fakeLockMutex = new FakeLockMutex();

        $redisMock
            ->expects($this->any())
            ->method('getMutex')
            ->willReturn($fakeLockMutex);

        return $redisMock;
    }
}
