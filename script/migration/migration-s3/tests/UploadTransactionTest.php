<?php

use App\CloudAccess\NewS3;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;
use App\Enum\Type;
use App\Service\UploadTransaction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UploadTransactionTest extends TestCase
{
    private SelfDB|MockObject $selfDBMock;
    private NewS3|MockObject $newS3Mock;
    private UploadTransaction $uploadTransaction;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selfDBMock = $this->createMock(SelfDB::class);
        $this->newS3Mock = $this->createMock(NewS3::class);
        $this->tempDir = sys_get_temp_dir() . '/upload_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->uploadTransaction = new UploadTransaction($this->selfDBMock, $this->newS3Mock, $this->tempDir);
    }

    protected function tearDown(): void
    {
        // Nettoyage du répertoire temporaire
        array_map('unlink', glob($this->tempDir . '/*'));
        rmdir($this->tempDir);
        parent::tearDown();
    }

    public function testRunFetchesDownloadedTransactions(): void
    {
        $this->selfDBMock->expects($this->once())
            ->method('getTransactionsByStatus')
            ->with(Status::DOWNLOADED, 10)
            ->willReturn([]);

        ob_start();
        $this->uploadTransaction->run(10);
        $output = ob_get_clean();

        $this->assertStringContainsString('Found 0 transactions', $output);
    }

    public function testProcessUploadSuccess(): void
    {
        $item = new MigrationItem(42, 'siren/file.tar.gz', Type::ACTE->value, '2023-01-01', 'siren');

        // Créer le fichier temporaire simulant le download
        $localPath = $this->tempDir . '/file.tar.gz-42';
        file_put_contents($localPath, 'fake_file_data');

        $this->newS3Mock->expects($this->once())
            ->method('upload')
            ->with('siren/file.tar.gz', $localPath, Type::ACTE->value)
            ->willReturn(true);

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::COMPLETED);



        ob_start();
        $this->uploadTransaction->processUpload($item);
        $output = ob_get_clean();

        $this->assertStringContainsString('UPLOADED', $output);
        // Le fichier local doit avoir été supprimé
        $this->assertFileDoesNotExist($localPath);
    }

    public function testProcessUploadFailedUpload(): void
    {
        $item = new MigrationItem(42, 'siren/file.tar.gz', Type::ACTE->value, '2023-01-01', 'siren');

        $localPath = $this->tempDir . '/file.tar.gz-42';
        file_put_contents($localPath, 'fake_file_data');

        $this->newS3Mock->expects($this->once())
            ->method('upload')
            ->willReturn(false);

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::ERROR, 'Failed to upload to New S3');



        ob_start();
        $this->uploadTransaction->processUpload($item);
        $output = ob_get_clean();

        $this->assertStringContainsString('UPLOAD FAILED', $output);
        // En cas d'erreur, le fichier local reste pour retry
        $this->assertFileExists($localPath);
    }

    public function testProcessUploadMissingLocalFile(): void
    {
        $item = new MigrationItem(42, 'siren/file.tar.gz', Type::ACTE->value, '2023-01-01', 'siren');

        // On ne crée pas le fichier -> simuler un crash entre download et upload
        $this->newS3Mock->expects($this->never())
            ->method('upload');

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::ERROR, 'Local downloaded file is missing.');

        ob_start();
        $this->uploadTransaction->processUpload($item);
        $output = ob_get_clean();

        $this->assertStringContainsString('FAILED (Local file missing', $output);
    }

    public function testRunProcessesMultipleTransactions(): void
    {
        $item1 = new MigrationItem(1, 'siren1/f1.tar.gz', Type::ACTE->value, '2023-01-01', 'siren1');
        $item2 = new MigrationItem(2, 'siren2/f2.tar.gz', Type::MAIL->value, '2023-02-01', 'siren2');

        // Créer les fichiers temporaires pour chacun
        file_put_contents($this->tempDir . '/f1.tar.gz-1', 'data1');
        file_put_contents($this->tempDir . '/f2.tar.gz-2', 'data2');

        $this->selfDBMock->expects($this->once())
            ->method('getTransactionsByStatus')
            ->with(Status::DOWNLOADED, 10)
            ->willReturn([$item1, $item2]);

        $this->newS3Mock->expects($this->exactly(2))
            ->method('upload')
            ->willReturn(true);

        $this->selfDBMock->expects($this->exactly(2))
            ->method('updateStatus')
            ->with($this->anything(), Status::COMPLETED);



        ob_start();
        $this->uploadTransaction->run(10);
        ob_end_clean();
    }
}
