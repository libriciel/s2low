<?php

namespace Tests;

use App\MigrationOrchestrator;
use NewS3;
use App\Repository\ActesRepository;
use App\SourceStorage;
use App\StateTrackerInterface;
use Aws\MockHandler;
use Aws\Result;
use Aws\Exception\AwsException;
use Aws\CommandInterface;
use PHPUnit\Framework\TestCase;

class MigrationOrchestratorTest extends TestCase
{
    private $source;
    private $newS3;
    private $stateTracker;
    private $sourceMockHandler;
    private $newS3MockHandler;

    protected function setUp(): void
    {
        // Source Mock Handler
        $this->sourceMockHandler = new MockHandler();
        // Use anonymous class for SourceStorage
        $this->source = new class ([
            'OLD_S3_REGION' => 'r', 'OLD_S3_ENDPOINT' => 'https://example.com',
            'OLD_S3_ACCESS_KEY' => 'k', 'OLD_S3_SECRET_KEY' => 's',
            'handler' => $this->sourceMockHandler
        ]) extends SourceStorage {
            public function getBucket(string $type, string $filename): string
            {
                return 'test-bucket';
            }
        };

        // NewS3 Mock Handler
        $this->newS3MockHandler = new MockHandler();
        // Use anonymous class for NewS3
        $this->newS3 = new class ('https://example.com', 'r', 'k', 's', ['handler' => $this->newS3MockHandler]) extends NewS3 {
            public function getBucket(string $type, string $filename): string
            {
                return 'test-bucket';
            }
        };

        // Mock StateTracker
        $this->stateTracker = $this->createMock(StateTrackerInterface::class);
        $this->stateTracker->method('getLastProcessedId')->willReturn(0);
        $this->stateTracker->method('isProcessed')->willReturn(false);
    }

    public function testRunActesDryRun()
    {
        // 1. SourceStorage->exists calls OldS3 HEAD -> Success
        $this->sourceMockHandler->append(new Result());

        // 2. NewS3->exists calls NewS3 HEAD -> Fail (Not exists)
        $this->newS3MockHandler->append(new AwsException('Not Found', $this->createMock(CommandInterface::class), ['code' => '404']));

        $repository = $this->createMock(ActesRepository::class);
        $repository->method('getBatch')->willReturnOnConsecutiveCalls(
            [
                ['id' => 1, 'file_path' => 'file1.tar.gz', 'siren' => '123']
            ],
            []
        );

        $orchestrator = $this->getMockBuilder(MigrationOrchestrator::class)
            ->setConstructorArgs([$this->source, $this->newS3, $this->stateTracker, true]) // dryRun=true
            ->onlyMethods(['getActesRepository'])
            ->getMock();

        $orchestrator->method('getActesRepository')->willReturn($repository);

        $this->expectOutputRegex('/\[DRY RUN\] Found in OldS3/');

        $orchestrator->runActes();
    }

    public function testActesResumeFromLastId()
    {
        $this->expectOutputRegex('/Starting actes migration from ID 100/');

        $this->stateTracker = $this->createMock(StateTrackerInterface::class);

        $this->stateTracker->expects($this->once())
            ->method('getLastProcessedId')
            ->with('actes')
            ->willReturn(100);

        // Mock Repository to expect getBatch with that ID
        $repository = $this->createMock(ActesRepository::class);
        $repository->expects($this->once())
            ->method('getBatch')
            ->with(100, 100)
            ->willReturn([]); // Empty batch to stop loop

        // Orchestrator
        $orchestrator = $this->getMockBuilder(MigrationOrchestrator::class)
            ->setConstructorArgs([$this->source, $this->newS3, $this->stateTracker, true]) // dryRun
            ->onlyMethods(['getActesRepository'])
            ->getMock();
        $orchestrator->method('getActesRepository')->willReturn($repository);

        $orchestrator->runActes();
    }

    public function testActesMigrationFlow()
    {
        $this->expectOutputRegex('/Processing ID 55/');

        // Mock Repository
        $repository = $this->createMock(ActesRepository::class);
        $repository->method('getBatch')->willReturnOnConsecutiveCalls(
            [['id' => 55, 'file_path' => 'data.tar.gz', 'siren' => '444']],
            []
        );

        // Mock StateTracker (New Mock for fresh expectations)
        $this->stateTracker = $this->createMock(StateTrackerInterface::class);

        // 1. Get Last ID
        $this->stateTracker->method('getLastProcessedId')->with('actes')->willReturn(0);
        // 2. Check if processed
        $this->stateTracker->expects($this->once())
            ->method('isProcessed')
            ->with('actes', 55)
            ->willReturn(false);
        // 3. Mark as Done
        $this->stateTracker->expects($this->once())
            ->method('markAsDone')
            ->with('actes', 55);

        // Mock SourceStorage. Since we need to test downloadFile logic inside SourceStorage (fallback etc),
        // we usually use the Real SourceStorage. BUT here we want to avoid real network.
        // In the previous version of this test, we mocked SourceStorage completely ($this->createMock).
        // If we mock it completely, we MUST configure it to return TRUE for `downloadFile`.
        // AND we don't need to worry about getBucket because the mock implementation of downloadFile takes over.

        // However, if we do `$this->createMock(SourceStorage::class)`, it creates a mock of the ORIGINAL class,
        // so if we don't stub a method, it returns null.

        // The original test code was:
        // $mockSource = $this->createMock(SourceStorage::class);
        // $mockSource->expects(...)->method('downloadFile')...

        // This is perfectly fine! The Mock Object methods don't execute the real code, so `getBucket` is never called
        // inside `downloadFile` OF THE MOCK.

        // WAIT. The error in the previous run was:
        // InvalidArgumentException: ... requires non-empty parameter: Bucket
        // Trace: NewS3.php:44
        // Logic: $this->newS3->upload(...)

        // So the failure was in NewS3, NOT SourceStorage.
        // My SourceStorage mock was fine.

        // So I will keep the SourceStorage mock logic as is (mocking downloadFile),
        // BUT I must ensure that $this->newS3 is the ANONYMOUS class (which I fixed in setUp).

        $mockSource = $this->createMock(SourceStorage::class);
        $mockSource->expects($this->once())
            ->method('downloadFile')
            ->with('data.tar.gz', true, $this->anything(), 'actes')
            ->will($this->returnCallback(function ($key, $prio, $path, $type) {
                file_put_contents($path, 'dummy content');
                return true;
            }));

        // We expect a PutObject command on NewS3
        $this->newS3MockHandler->append(new Result()); // 200 OK for PutObject

        // Orchestrator (NOT dry run)
         // Note: We use $this->newS3 which is the anonymous class created in setUp
         $orchestrator = $this->getMockBuilder(MigrationOrchestrator::class)
            ->setConstructorArgs([$mockSource, $this->newS3, $this->stateTracker, false])
            ->onlyMethods(['getActesRepository'])
            ->getMock();
         $orchestrator->method('getActesRepository')->willReturn($repository);

        $orchestrator->runActes();
    }
}
