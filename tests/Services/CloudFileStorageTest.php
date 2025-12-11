<?php

declare(strict_types=1);

namespace S2low\Tests\Services;

use org\bovigo\vfs\vfsStream;
use phpseclib3\Exception\FileNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S2low\Exceptions\CloudDownloadException;
use S2low\Exceptions\CloudException;
use S2low\Exceptions\CloudFileDeletionException;
use S2low\Exceptions\CloudFileUploadException;
use S2low\Port\CloudClientInterface;
use S2low\Services\CloudFileStorage;
use S2low\Services\FileDataProvider;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\CloudStorageException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \S2low\Services\CloudFileStorage
 */
final class CloudFileStorageTest extends TestCase
{
    private CloudClientInterface&MockObject $cloudClient;
    private LocalFileResolver&MockObject $localFileResolver;
    private FileDataProvider&MockObject $fileDataProvider;
    private Filesystem&MockObject $filesystem;
    private CloudFileStorage $cloudFileStorage;

    private string $vfsRoot;

    protected function setUp(): void
    {
        $this->cloudClient = $this->createMock(CloudClientInterface::class);
        $this->localFileResolver = $this->createMock(LocalFileResolver::class);
        $this->fileDataProvider = $this->createMock(FileDataProvider::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->cloudFileStorage = new CloudFileStorage(
            $this->cloudClient,
            $this->localFileResolver,
            $this->fileDataProvider,
            $this->filesystem
        );

        vfsStream::setup('test');
        $this->vfsRoot = vfsStream::url('test');
    }

    /**
     * Vérifie que le fichier est correctement uploadé sur le cloud quand il existe localement
     */
    public function testStoreFileOnCloudSuccess(): void
    {
        $transactionId = 'tx-123';
        $filePath = $this->vfsRoot . '/test-file.pdf';
        $cloudId = 'cloud-id-123';

        file_put_contents($filePath, 'test content');

        $this->localFileResolver
            ->expects(self::once())
            ->method('getFullPath')
            ->with($transactionId)
            ->willReturn($filePath);

        $this->fileDataProvider
            ->expects(self::once())
            ->method('getCloudId')
            ->with($transactionId)
            ->willReturn($cloudId);

        $this->cloudClient
            ->expects(self::once())
            ->method('uploadFile')
            ->with($filePath, $cloudId);

        $this->fileDataProvider
            ->expects(self::once())
            ->method('setTransactionIsInCloud')
            ->with($transactionId);

        $this->cloudFileStorage->storeFileOnCloud($transactionId);
    }

    /**
     * Vérifie qu'une exception FileNotFoundException est levée si le fichier local n'existe pas
     */
    public function testStoreFileOnCloudThrowsExceptionWhenFileNotExists(): void
    {
        $transactionId = 'tx-123';
        $filePath = $this->vfsRoot . '/non-existent-file.pdf';
        $cloudId = 'cloud-id-123';

        $this->localFileResolver
            ->method('getFullPath')
            ->willReturn($filePath);

        $this->fileDataProvider
            ->method('getCloudId')
            ->willReturn($cloudId);

        $this->expectException(FileNotFoundException::class);

        $this->cloudFileStorage->storeFileOnCloud($transactionId);
    }

    /**
     * Vérifie qu'une CloudFileUploadException est levée en cas d'erreur lors de l'upload
     */
    public function testStoreFileOnCloudThrowsCloudFileUploadExceptionOnUploadError(): void
    {
        $transactionId = 'tx-123';
        $filePath = $this->vfsRoot . '/test-file.pdf';
        $cloudId = 'cloud-id-123';

        file_put_contents($filePath, 'test content');

        $this->localFileResolver
            ->method('getFullPath')
            ->willReturn($filePath);

        $this->fileDataProvider
            ->method('getCloudId')
            ->willReturn($cloudId);

        $this->cloudClient
            ->method('uploadFile')
            ->willThrowException(new CloudStorageException('Upload failed'));

        $this->expectException(CloudFileUploadException::class);

        $this->cloudFileStorage->storeFileOnCloud($transactionId);
    }

    /**
     * Vérifie que le fichier est correctement téléchargé depuis le cloud
     */
    public function testDownloadFileFromCloudSuccess(): void
    {
        $transactionId = 'tx-123';
        mkdir($this->vfsRoot . '/subdir', 0777, true);
        $filePath = $this->vfsRoot . '/subdir/downloaded-file.pdf';
        $cloudId = 'cloud-id-123';

        $this->localFileResolver
            ->expects(self::once())
            ->method('getFullPath')
            ->with($transactionId)
            ->willReturn($filePath);

        $this->fileDataProvider
            ->expects(self::once())
            ->method('getCloudId')
            ->with($transactionId)
            ->willReturn($cloudId);

        $this->filesystem
            ->expects(self::once())
            ->method('mkdir')
            ->with($this->vfsRoot . '/subdir');

        $this->cloudClient
            ->expects(self::once())
            ->method('downloadFile')
            ->with($filePath, $cloudId);

        $this->cloudFileStorage->downloadFileFromCloud($transactionId);
    }

    /**
     * Vérifie que le téléchargement est ignoré si le fichier existe déjà localement
     */
    public function testDownloadFileFromCloudSkipsIfFileExists(): void
    {
        $transactionId = 'tx-123';
        $filePath = $this->vfsRoot . '/existing-file.pdf';

        file_put_contents($filePath, 'existing content');

        $this->localFileResolver
            ->method('getFullPath')
            ->willReturn($filePath);

        $this->cloudClient
            ->expects(self::never())
            ->method('downloadFile');

        $this->cloudFileStorage->downloadFileFromCloud($transactionId);
    }

    /**
     * Vérifie qu'une CloudDownloadException est levée en cas d'erreur lors du téléchargement
     */
    public function testDownloadFileFromCloudThrowsCloudDownloadExceptionOnError(): void
    {
        $transactionId = 'tx-123';
        $filePath = $this->vfsRoot . '/new-file.pdf';
        $cloudId = 'cloud-id-123';

        $this->localFileResolver
            ->method('getFullPath')
            ->willReturn($filePath);

        $this->fileDataProvider
            ->method('getCloudId')
            ->willReturn($cloudId);

        $this->cloudClient
            ->method('downloadFile')
            ->willThrowException(new CloudStorageException('Download failed'));

        $this->expectException(CloudDownloadException::class);

        $this->cloudFileStorage->downloadFileFromCloud($transactionId);
    }

    /**
     * Vérifie que le fichier est correctement supprimé du cloud
     */
    public function testDeleteFileFromCloudSuccess(): void
    {
        $transactionId = 'tx-123';
        $cloudId = 'cloud-id-123';

        $this->fileDataProvider
            ->expects(self::once())
            ->method('getCloudId')
            ->with($transactionId)
            ->willReturn($cloudId);

        $this->cloudClient
            ->expects(self::once())
            ->method('deleteFile')
            ->with($cloudId);

        $this->cloudFileStorage->deleteFileFromCloud($transactionId);
    }

    /**
     * Vérifie qu'une CloudFileDeletionException est levée en cas d'erreur lors de la suppression
     */
    public function testDeleteFileFromCloudThrowsCloudFileDeletionExceptionOnError(): void
    {
        $transactionId = 'tx-123';
        $cloudId = 'cloud-id-123';

        $this->fileDataProvider
            ->method('getCloudId')
            ->willReturn($cloudId);

        $this->cloudClient
            ->method('deleteFile')
            ->willThrowException(new CloudStorageException('Delete failed'));

        $this->expectException(CloudFileDeletionException::class);

        $this->cloudFileStorage->deleteFileFromCloud($transactionId);
    }

    /**
     * Vérifie que la méthode retourne true quand le fichier existe sur le cloud
     */
    public function testFileExistOnCloudReturnsTrue(): void
    {
        $transactionId = 'tx-123';
        $cloudId = 'cloud-id-123';

        $this->fileDataProvider
            ->expects(self::once())
            ->method('getCloudId')
            ->with($transactionId)
            ->willReturn($cloudId);

        $this->cloudClient
            ->expects(self::once())
            ->method('fileExists')
            ->with($cloudId)
            ->willReturn(true);

        $result = $this->cloudFileStorage->fileExistOnCloud($transactionId);

        $this->assertTrue($result);
    }

    /**
     * Vérifie que la méthode retourne false quand le fichier n'existe pas sur le cloud
     */
    public function testFileExistOnCloudReturnsFalse(): void
    {
        $transactionId = 'tx-123';
        $cloudId = 'cloud-id-123';

        $this->fileDataProvider
            ->method('getCloudId')
            ->willReturn($cloudId);

        $this->cloudClient
            ->method('fileExists')
            ->willReturn(false);

        $result = $this->cloudFileStorage->fileExistOnCloud($transactionId);

        $this->assertFalse($result);
    }

    /**
     * Vérifie qu'une CloudException est levée en cas d'erreur lors de la vérification d'existence
     */
    public function testFileExistOnCloudThrowsCloudExceptionOnError(): void
    {
        $transactionId = 'tx-123';
        $cloudId = 'cloud-id-123';

        $this->fileDataProvider
            ->method('getCloudId')
            ->willReturn($cloudId);

        $this->cloudClient
            ->method('fileExists')
            ->willThrowException(new CloudStorageException('Check failed'));

        $this->expectException(CloudException::class);

        $this->cloudFileStorage->fileExistOnCloud($transactionId);
    }
}
