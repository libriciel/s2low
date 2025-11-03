<?php

namespace Services;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2low\Services\RemoveStoredFilesOnDisk;
use S2lowLegacy\Class\TmpFolder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\FileDataProvider;
use S2low\Services\LocalFileResolver;

class RemoveOldFilesOnDiskTest extends TestCase
{
    private LoggerInterface $logger;
    private Filesystem $filesystem;
    private CloudFileStorageInterface $cloudStorage;
    private FileDataProvider $fileDataProvider;
    private LocalFileResolver $localFileResolver;
    private Finder $finder;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = new Filesystem();
        $this->cloudStorage = $this->createMock(CloudFileStorageInterface::class);
        $this->fileDataProvider = $this->createMock(FileDataProvider::class);
        $this->localFileResolver = $this->createMock(LocalFileResolver::class);
        $this->finder = new Finder();
        $this->pesAllerPrefix = (new TmpFolder())->create();
        $this->tempDir = sys_get_temp_dir() . '/remove_old_files_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob("$this->tempDir/*"));
            rmdir($this->tempDir);
        }
    }

    public function testExecuteDeletesOldFileWhenItExistsInCloud(): void
    {
        // Arrange
        $filePath = $this->tempDir . '/transaction123.txt';
        file_put_contents($filePath, 'dummy');

        // Fichier vieux de 20 jours
        touch($filePath, time() - (20 * 86400));

        $file = new SplFileInfo($filePath, '', basename($filePath));

        $this->finder->files()->in($this->tempDir);

        $this->fileDataProvider
            ->method('getTransactionIdFromFileName')
            ->willReturn('transaction123');

        $this->cloudStorage
            ->method('fileExistOnCloud')
            ->with('transaction123')
            ->willReturn(true);

        $this->localFileResolver
            ->method('getFullPath')
            ->with('transaction123')
            ->willReturn($filePath);

        $service = new RemoveStoredFilesOnDisk(
            $this->logger,
            $this->filesystem,
            $this->cloudStorage,
            $this->fileDataProvider,
            $this->localFileResolver,
            $this->finder,
            $this->pesAllerPrefix,
            $this->tempDir,
            false
        );

        // Act
        $service->findAndRemoveLocalFilesAlreadyCloudSaved(15);

        // Assert
        $this->assertFileDoesNotExist($filePath, 'Le fichier doit être supprimé.');
    }

    public function testExecuteMovesOrphanFilesWhenOptionIsEnabled(): void
    {
        // Arrange
        $filePath = $this->tempDir . '/orphan.txt';
        file_put_contents($filePath, 'orphan file');
        touch($filePath, time() - (20 * 86400));

        $finder = new Finder();
        $finder->files()->in($this->tempDir);

        $this->fileDataProvider
            ->method('getTransactionIdFromFileName')
            ->willReturn(null);

        $service = new RemoveStoredFilesOnDisk(
            $this->logger,
            $this->filesystem,
            $this->cloudStorage,
            $this->fileDataProvider,
            $this->localFileResolver,
            $finder,
            $this->pesAllerPrefix,
            $this->tempDir,
            true
        );

        // Act
        $service->findAndRemoveLocalFilesAlreadyCloudSaved(15);

        // Assert
        $movedFilePath = $this->tempDir . '/orphan.txt';
        $this->assertFileExists($movedFilePath, 'Le fichier orphelin doit être déplacé.');
    }

    public function testExecuteSkipsYoungFiles(): void
    {
        // Arrange
        $filePath = $this->tempDir . '/young.txt';
        file_put_contents($filePath, 'recent file');
        touch($filePath, time() - (2 * 86400)); // 2 jours

        $finder = new Finder();
        $finder->files()->in($this->tempDir);

        $this->fileDataProvider
            ->method('getTransactionIdFromFileName')
            ->willReturn('young123');

        $service = new RemoveStoredFilesOnDisk(
            $this->logger,
            $this->filesystem,
            $this->cloudStorage,
            $this->fileDataProvider,
            $this->localFileResolver,
            $finder,
            $this->pesAllerPrefix,
            $this->tempDir,
            false
        );

        // Act
        $service->findAndRemoveLocalFilesAlreadyCloudSaved(15);

        // Assert
        $this->assertFileExists($filePath, 'Les fichiers récents ne doivent pas être supprimés.');
    }
}
