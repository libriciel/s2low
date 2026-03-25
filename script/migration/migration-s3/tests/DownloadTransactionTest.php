<?php

use App\CloudAccess\OldS3;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;
use App\Enum\Type;
use App\Service\DownloadTransaction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DownloadTransactionTest extends TestCase
{
    private SelfDB|MockObject $selfDBMock;
    private OldS3|MockObject $oldS3Mock;
    private DownloadTransaction $downloadTransaction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selfDBMock = $this->createMock(SelfDB::class);
        $this->oldS3Mock = $this->createMock(OldS3::class);
        $this->downloadTransaction = new DownloadTransaction($this->selfDBMock, $this->oldS3Mock, '/tmp');
    }

    public function testRunFetchesBucketFoundAndAskTransactions(): void
    {
        $item1 = new MigrationItem(1, 'key1', Type::ACTE->value, '2023-01-01', 'siren1', 'bucket-actes-2023');
        $item2 = new MigrationItem(2, 'key2', Type::PES_ALLER->value, '2023-06-01', 'siren2', 'bucket-helios-2023');

        $this->selfDBMock->expects($this->exactly(2))
            ->method('getTransactionsByStatus')
            ->willReturnMap([
                [Status::BUCKET_FOUND, 10, null, [$item1]],
                [Status::ASK, 10, null, [$item2]],
            ]);

        // Both will call getFile
        $this->oldS3Mock->expects($this->exactly(2))
            ->method('getFile')
            ->willReturn(['current_status' => 'telechargé']);

        $this->selfDBMock->expects($this->exactly(2))
            ->method('updateStatus')
            ->with($this->anything(), Status::DOWNLOADED);

        ob_start();
        $this->downloadTransaction->run(10);
        ob_end_clean();
    }

    public function testRunWithNoTransactions(): void
    {
        $this->selfDBMock->expects($this->exactly(2))
            ->method('getTransactionsByStatus')
            ->willReturn([]);

        $this->oldS3Mock->expects($this->never())
            ->method('getFile');

        ob_start();
        $this->downloadTransaction->run(10);
        $output = ob_get_clean();

        $this->assertStringContainsString('Found 0 transactions', $output);
    }

    public function testProcessDownloadSuccessful(): void
    {
        $item = new MigrationItem(42, 'siren1/file.tar.gz', Type::ACTE->value, '2023-01-01', 'siren1', 'my-bucket');

        $this->oldS3Mock->expects($this->once())
            ->method('getFile')
            ->with('my-bucket', 'siren1/file.tar.gz', '/tmp/file.tar.gz-42', true)
            ->willReturn(['current_status' => 'telechargé']);

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::DOWNLOADED);

        ob_start();
        $this->downloadTransaction->processDownload($item);
        $output = ob_get_clean();

        $this->assertStringContainsString('DONE', $output);
    }

    public function testProcessDownloadGlacierRestore(): void
    {
        $item = new MigrationItem(10, 'key', Type::PES_ALLER->value, '2020-01-01', 'siren', 'bucket');

        $this->oldS3Mock->expects($this->once())
            ->method('getFile')
            ->willReturn(['current_status' => 'en attente de restoration']);

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::ASK);

        ob_start();
        $this->downloadTransaction->processDownload($item);
        $output = ob_get_clean();

        $this->assertStringContainsString('ASKED RESTORE', $output);
    }

    public function testProcessDownloadError(): void
    {
        $item = new MigrationItem(10, 'key', Type::ACTE->value, '2020-01-01', 'siren', 'bucket');

        $this->oldS3Mock->expects($this->once())
            ->method('getFile')
            ->willReturn(['current_status' => 'erreur: 404 Not Found']);

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::ERROR, 'erreur: 404 Not Found');

        ob_start();
        $this->downloadTransaction->processDownload($item);
        $output = ob_get_clean();

        $this->assertStringContainsString('ERROR', $output);
    }

    public function testProcessDownloadFrozenStatus(): void
    {
        $item = new MigrationItem(10, 'key', Type::ACTE->value, '2020-01-01', 'siren', 'bucket');

        $this->oldS3Mock->expects($this->once())
            ->method('getFile')
            ->willReturn(['current_status' => 'frozen']);

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::ASK);

        ob_start();
        $this->downloadTransaction->processDownload($item);
        $output = ob_get_clean();

        $this->assertStringContainsString('frozen', $output);
    }

    public function testProcessDownloadUnknownStatus(): void
    {
        $item = new MigrationItem(10, 'key', Type::ACTE->value, '2020-01-01', 'siren', 'bucket');

        $this->oldS3Mock->expects($this->once())
            ->method('getFile')
            ->willReturn(['current_status' => 'bizarre_status']);

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::ERROR, 'Unknown S3 status: bizarre_status');

        ob_start();
        $this->downloadTransaction->processDownload($item);
        ob_end_clean();
    }

    public function testProcessDownloadUsesFallbackBucketWhenNull(): void
    {
        $item = new MigrationItem(10, 'key', Type::ACTE->value, '2020-01-01', 'siren', null);
        $_ENV['OLD_S3_BUCKET_NAME'] = 'env-fallback-bucket';

        $this->oldS3Mock->expects($this->once())
            ->method('getFile')
            ->with('env-fallback-bucket', $this->anything(), $this->anything(), true)
            ->willReturn(['current_status' => 'telechargé']);

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::DOWNLOADED);

        ob_start();
        $this->downloadTransaction->processDownload($item);
        ob_end_clean();

        unset($_ENV['OLD_S3_BUCKET_NAME']);
    }
}
