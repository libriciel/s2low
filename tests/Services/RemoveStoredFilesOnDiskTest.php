<?php

declare(strict_types=1);

namespace S2low\Tests\Services;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\FileDataProvider;
use S2low\Services\LocalFileResolver;
use S2low\Services\RemoveStoredFilesOnDisk;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @covers \S2low\Services\RemoveStoredFilesOnDisk
 */
final class RemoveStoredFilesOnDiskTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private Filesystem&MockObject $filesystem;
    private CloudFileStorageInterface&MockObject $cloudFileStorage;
    private FileDataProvider&MockObject $fileDataProvider;
    private LocalFileResolver&MockObject $localFileResolver;
    private Finder $finder;
    private string $vfsRoot;
    private string $errorDir;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->cloudFileStorage = $this->createMock(CloudFileStorageInterface::class);
        $this->fileDataProvider = $this->createMock(FileDataProvider::class);
        $this->localFileResolver = $this->createMock(LocalFileResolver::class);

        vfsStream::setup('test');
        $this->vfsRoot = vfsStream::url('test');
        $this->errorDir = $this->vfsRoot . '/errors';
        mkdir($this->errorDir, 0777, true);

        $this->finder = new Finder();
        $this->finder->in($this->vfsRoot)->files();
    }

    private function createRemoveStoredFilesOnDisk(bool $moveOrphelinsFiles = true): RemoveStoredFilesOnDisk
    {
        return new RemoveStoredFilesOnDisk(
            $this->logger,
            $this->filesystem,
            $this->cloudFileStorage,
            $this->fileDataProvider,
            $this->localFileResolver,
            $this->finder,
            $this->vfsRoot,
            $this->errorDir,
            $moveOrphelinsFiles
        );
    }

    /**
     * Vérifie que le fichier local est supprimé quand il existe sur le cloud
     */
    public function testDeleteFileIfSavedOnCloudDeletesFileWhenExistsOnCloud(): void
    {
        $transactionId = 'tx-123';
        $filePath = $this->vfsRoot . '/test-file.pdf';

        $this->cloudFileStorage
            ->expects(self::once())
            ->method('fileExistOnCloud')
            ->with($transactionId)
            ->willReturn(true);

        $this->localFileResolver
            ->expects(self::once())
            ->method('getFullPath')
            ->with($transactionId)
            ->willReturn($filePath);

        $this->filesystem
            ->expects(self::once())
            ->method('exists')
            ->with($filePath)
            ->willReturn(true);

        $this->filesystem
            ->expects(self::once())
            ->method('remove')
            ->with($filePath);

        $removeService = $this->createRemoveStoredFilesOnDisk();
        $removeService->deleteFileIfSavedOnCloud($transactionId);
    }

    /**
     * Vérifie que le fichier local n'est pas supprimé quand il n'existe pas sur le cloud
     */
    public function testDeleteFileIfSavedOnCloudDoesNotDeleteWhenNotOnCloud(): void
    {
        $transactionId = 'tx-123';

        $this->cloudFileStorage
            ->expects(self::once())
            ->method('fileExistOnCloud')
            ->with($transactionId)
            ->willReturn(false);

        $this->filesystem
            ->expects(self::never())
            ->method('remove');

        $removeService = $this->createRemoveStoredFilesOnDisk();
        $removeService->deleteFileIfSavedOnCloud($transactionId);
    }

    /**
     * Vérifie qu'un message de log est émis quand le fichier local n'existe pas
     */
    public function testDeleteFileIfSavedOnCloudLogsWhenFileNotFound(): void
    {
        $transactionId = 'tx-123';
        $filePath = $this->vfsRoot . '/non-existent.pdf';

        $this->cloudFileStorage
            ->method('fileExistOnCloud')
            ->willReturn(true);

        $this->localFileResolver
            ->method('getFullPath')
            ->willReturn($filePath);

        $this->filesystem
            ->method('exists')
            ->willReturn(false);

        $this->filesystem
            ->expects(self::never())
            ->method('remove');

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with($this->stringContains("n'existe pas"));

        $removeService = $this->createRemoveStoredFilesOnDisk();
        $removeService->deleteFileIfSavedOnCloud($transactionId);
    }

    /**
     * Vérifie qu'aucun traitement n'est effectué quand le répertoire est vide
     */
    public function testFindAndRemoveLocalFilesAlreadyCloudSavedWithNoFiles(): void
    {
        $this->cloudFileStorage
            ->expects(self::never())
            ->method('fileExistOnCloud');

        $removeService = $this->createRemoveStoredFilesOnDisk();
        $removeService->findAndRemoveLocalFilesAlreadyCloudSaved();
    }

    /**
     * Vérifie que les fichiers récents (moins de 15 jours) sont ignorés
     */
    public function testFindAndRemoveLocalFilesAlreadyCloudSavedSkipsRecentFiles(): void
    {
        // Création d'un fichier récent (doit être ignoré à cause de l'âge)
        $filePath = $this->vfsRoot . '/recent-file.pdf';
        file_put_contents($filePath, 'content');
        touch($filePath, time()); // Définit la date de modification à maintenant

        // Recréation du finder pour prendre en compte le nouveau fichier
        $this->finder = new Finder();
        $this->finder->in($this->vfsRoot)->files()->notPath('errors');

        $this->cloudFileStorage
            ->expects(self::never())
            ->method('fileExistOnCloud');

        $removeService = $this->createRemoveStoredFilesOnDisk();
        $removeService->findAndRemoveLocalFilesAlreadyCloudSaved(15);
    }

    /**
     * Vérifie que les fichiers anciens (plus de 15 jours) sont traités et supprimés s'ils sont sur le cloud
     */
    public function testFindAndRemoveLocalFilesAlreadyCloudSavedProcessesOldFiles(): void
    {
        // Création d'un fichier ancien
        $filePath = $this->vfsRoot . '/old-file.pdf';
        file_put_contents($filePath, 'content');
        touch($filePath, time() - (20 * 86400)); // 20 jours

        // Recréation du finder
        $this->finder = new Finder();
        $this->finder->in($this->vfsRoot)->files()->notPath('errors');

        $transactionId = 'tx-123';

        $this->fileDataProvider
            ->method('getTransactionIdFromFileName')
            ->willReturn($transactionId);

        $this->cloudFileStorage
            ->expects(self::once())
            ->method('fileExistOnCloud')
            ->with($transactionId)
            ->willReturn(true);

        $this->localFileResolver
            ->method('getFullPath')
            ->willReturn($filePath);

        $this->filesystem
            ->method('exists')
            ->willReturn(true);

        $this->filesystem
            ->expects(self::once())
            ->method('remove')
            ->with($filePath);

        $removeService = $this->createRemoveStoredFilesOnDisk();
        $removeService->findAndRemoveLocalFilesAlreadyCloudSaved(15);
    }

    /**
     * Vérifie que les fichiers orphelins (sans transaction associée) sont déplacés vers le répertoire d'erreurs
     */
    public function testFindAndRemoveLocalFilesAlreadyCloudSavedMovesOrphanFiles(): void
    {
        // Création d'un fichier ancien sans transaction
        $filePath = $this->vfsRoot . '/orphan-file.pdf';
        file_put_contents($filePath, 'content');
        touch($filePath, time() - (20 * 86400));

        // Recréation du finder
        $this->finder = new Finder();
        $this->finder->in($this->vfsRoot)->files()->notPath('errors');

        $this->fileDataProvider
            ->method('getTransactionIdFromFileName')
            ->willReturn(null);

        $this->filesystem
            ->expects(self::once())
            ->method('rename')
            ->with($filePath, $this->errorDir . '/orphan-file.pdf');

        $removeService = $this->createRemoveStoredFilesOnDisk(true);
        $removeService->findAndRemoveLocalFilesAlreadyCloudSaved(15);
    }

    /**
     * Vérifie que les fichiers orphelins ne sont pas déplacés quand l'option moveOrphelinsFiles est désactivée
     */
    public function testFindAndRemoveLocalFilesAlreadyCloudSavedDoesNotMoveOrphanFilesWhenDisabled(): void
    {
        // Création d'un fichier ancien sans transaction
        $filePath = $this->vfsRoot . '/orphan-file.pdf';
        file_put_contents($filePath, 'content');
        touch($filePath, time() - (20 * 86400));

        // Recréation du finder
        $this->finder = new Finder();
        $this->finder->in($this->vfsRoot)->files()->notPath('errors');

        $this->fileDataProvider
            ->method('getTransactionIdFromFileName')
            ->willReturn(null);

        $this->filesystem
            ->expects(self::never())
            ->method('rename');

        $removeService = $this->createRemoveStoredFilesOnDisk(false);
        $removeService->findAndRemoveLocalFilesAlreadyCloudSaved(15);
    }
}
