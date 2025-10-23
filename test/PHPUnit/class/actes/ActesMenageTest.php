<?php

declare(strict_types=1);

namespace PHPUnit\Class\Actes;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesMenage;
use S2low\Services\CloudFileStorageInterface;
use S2lowLegacy\Lib\SigTermHandler;

class ActesMenageTest extends TestCase
{
    private $logger;
    private $cloudStorage;
    private $actesEnvelopeSql;
    private $root;
    private $sigtermHandler;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cloudStorage = $this->createMock(CloudFileStorageInterface::class);
        $this->actesEnvelopeSql = $this->createMock(ActesEnvelopeSQL::class);
        $this->root = vfsStream::setup('uploads');

        // Mock SigTermHandler::getInstance()
        $this->sigtermHandler = $this->createMock(SigTermHandler::class);
        $this->sigtermHandler->method('isSigtermCalled')->willReturn(false);

        $ref = new \ReflectionClass(SigTermHandler::class);
        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue($this->sigtermHandler);
    }

    private function createService(): ActesMenage
    {
        return new ActesMenage(
            vfsStream::url('uploads'),
            $this->actesEnvelopeSql,
            $this->logger,
            $this->cloudStorage
        );
    }

    private function createFile(string $name): string
    {
        vfsStream::newFile($name)->at($this->root)->setContent('dummy');
        return $name;
    }

    private function createQueryStub(array $envelope): object
    {
        $callCount = 0;

        return new class ($envelope, $callCount) {
            private $envelope;
            private $callCount;

            public function __construct($envelope, &$callCount)
            {
                $this->envelope = $envelope;
                $this->callCount = &$callCount;
            }

            public function hasMoreResult(): bool
            {
                return $this->callCount++ === 0;
            }

            public function fetch(): array
            {
                return $this->envelope;
            }
        };
    }

    private function createMultiQueryStub(array $envelopes): object
    {
        $callCount = 0;
        return new class ($envelopes, $callCount) {
            private $envelopes;
            private $callCount;
            public function __construct($envelopes, &$callCount)
            {
                $this->envelopes = $envelopes;
                $this->callCount = &$callCount;
            }
            public function hasMoreResult(): bool
            {
                return $this->callCount < count($this->envelopes);
            }
            public function fetch(): array
            {
                return $this->envelopes[$this->callCount++];
            }
        };
    }

    public function testSupprimeLeFichierSiPresentSurCloudEtConfirmTrue(): void
    {
        $filePath = $this->createFile('file.txt');

        $this->actesEnvelopeSql->method('getOlderTransactionHandle')
            ->willReturn($this->createQueryStub(['id' => 123, 'file_path' => $filePath]));

        $this->cloudStorage->method('fileExistOnCloud')->willReturn(true);

        $this->createService()->grandMenage('2024-01-01', '2024-12-31', true);

        $this->assertFalse(file_exists(vfsStream::url("uploads/$filePath")));
    }

    public function testNeSupprimePasSiConfirmFalse(): void
    {
        $filePath = $this->createFile('file.txt');

        $this->actesEnvelopeSql->method('getOlderTransactionHandle')
            ->willReturn($this->createQueryStub(['id' => 42, 'file_path' => $filePath]));

        $this->cloudStorage->method('fileExistOnCloud')->willReturn(true);

        $this->createService()->grandMenage('2024-01-01', '2024-12-31', false);

        $this->assertTrue(file_exists(vfsStream::url("uploads/$filePath")));
    }

    public function testIgnoreSiFichierAbsent(): void
    {
        $this->actesEnvelopeSql->method('getOlderTransactionHandle')
            ->willReturn($this->createQueryStub(['id' => 77, 'file_path' => 'ghost.txt']));

        $this->cloudStorage->expects($this->never())->method('fileExistOnCloud');

        $this->createService()->grandMenage('2024-01-01', '2024-12-31', true);

        $this->assertTrue(true);
    }

    public function testNeSupprimePasSiFichierPasSurCloud(): void
    {
        $filePath = $this->createFile('local_only.txt');

        $this->actesEnvelopeSql->method('getOlderTransactionHandle')
            ->willReturn($this->createQueryStub(['id' => 999, 'file_path' => $filePath]));

        $this->cloudStorage->method('fileExistOnCloud')->willReturn(false);

        $this->createService()->grandMenage('2024-01-01', '2024-12-31', true);

        $this->assertTrue(file_exists(vfsStream::url("uploads/$filePath")));
    }

    public function testSupprimePlusieursFichiers(): void
    {
        $file1 = $this->createFile('f1.txt');
        $file2 = $this->createFile('f2.txt');

        $this->actesEnvelopeSql->method('getOlderTransactionHandle')
            ->willReturn($this->createMultiQueryStub([
                ['id' => 10, 'file_path' => $file1],
                ['id' => 11, 'file_path' => $file2],
            ]));

        $this->cloudStorage->method('fileExistOnCloud')->willReturn(true);

        $this->createService()->grandMenage('2024-01-01', '2024-12-31', true);

        $this->assertFalse(file_exists(vfsStream::url("uploads/$file1")));
        $this->assertFalse(file_exists(vfsStream::url("uploads/$file2")));
    }
}
