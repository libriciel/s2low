<?php

namespace S2low\Services;

use Psr\Log\LoggerInterface;
use S2low\Exceptions\FileDeletionException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class RemoveStoredFilesOnDisk
{
    private const MAX_FILES_AGE = 15;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Filesystem $filesystem,
        private readonly CloudFileStorageInterface $cloudFileStorage,
        private readonly FileDataProvider $fileDataProvider,
        private readonly LocalFileResolver $localFileResolver,
        private readonly Finder $finder,
        private readonly string $localPathPrefix,
        private readonly ?string $transactionErrorDirectory,
        private readonly bool $moveOrphelinsFiles
    ) {
    }

    public function findAndRemoveLocalFilesAlreadyCloudSaved(int $maxFilesAge = self::MAX_FILES_AGE): void
    {
        $transactionsIds = $this->getTransactionsIdsOfFilesFoundAndMoveOrphelinsFiles($maxFilesAge);
        $this->logger->debug(
            sprintf("Transactions pour lesquelles ont doit faire le menage : %s.", json_encode($transactionsIds)),
        );

        foreach ($transactionsIds as $transactionId) {
            $this->logger->debug(
                sprintf("Prise en charge de la transaction %s", $transactionId)
            );
            $this->deleteFileIfSavedOnCloud($transactionId);
        }
    }

    private function getTransactionsIdsOfFilesFoundAndMoveOrphelinsFiles(int $maxFilesAge): array
    {
//        $sigtermHandler = SigTermHandler::getInstance();

        $transactionsIds = [];

        foreach ($this->finder as $file) {
            $transactionId = $this->fileDataProvider->getTransactionIdFromFileName($file->getFilename());

            if ($this->fileIsYoungerThan($file, $maxFilesAge)) {
                $this->logger->debug(
                    sprintf('le fichier %s est trop recent pour etre supprimé', basename($file))
                );

                continue;
            }

            if ($transactionId === null) {
                $this->logger->debug(
                    sprintf('Pas de transaction associé au fichier %s', basename($file))
                );
                if ($this->moveOrphelinsFiles) {
                    $this->moveFileToOrphelinsDirectory($file);
                    $this->logger->debug(
                        sprintf('Déplacement du fichier %s', basename($file))
                    );
                }

                continue;
            }

            $this->logger->debug(
                sprintf("Le fichier est associé à la transaction %s", $transactionId)
            );

            $transactionsIds[] = $transactionId;
        }

        return $transactionsIds;
    }

    /**
     * @param mixed $transactionId
     * @return void
     */
    public function deleteFileIfSavedOnCloud(string $transactionId): void
    {
        if ($this->cloudFileStorage->fileExistOnCloud($transactionId)) {
            try {
                $this->deleteTransactionFileOnDisk($transactionId);
                $this->logger->info(
                    sprintf('Suppression du fichier de la transaction %s.', $transactionId)
                );
            } catch (FileDeletionException $exception) {
                $this->logger->error($exception->getMessage());
            }
        } else {
            $this->logger->debug(
                sprintf(
                    'Le fichier de la transaction %s n\'est pas present dans le cloud. Abandon du traitement.',
                    $transactionId
                )
            );
        }
    }

    private function fileIsYoungerThan(SplFileInfo $file, int $maxFilesAge): bool
    {
        $lastAccessTime = $file->getMTime();
        $secondsSinceLastAccess = time() - $lastAccessTime;
        $maxFileAgeInSecond = $maxFilesAge * 86400;

        return ($secondsSinceLastAccess < $maxFileAgeInSecond);
    }

    private function moveFileToOrphelinsDirectory(string $filePath): void
    {
        try {
            $this->filesystem->rename($filePath, $this->transactionErrorDirectory . '/' . basename($filePath));
            $this->filesystem->remove($filePath);
        } catch (IOExceptionInterface $exception) {
            $this->logger->error(
                'Une erreur est survenue pendant la tentative de déplacement du fichier ' . basename($filePath)
            );
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * @throws FileDeletionException
     */
    private function deleteTransactionFileOnDisk($transactionId): void
    {
        $path = $this->localFileResolver->getFullPath($transactionId);

        $this->deleteFile($path);
    }

    /**
     * @throws FileDeletionException
     */
    private function deleteFile(string $path): void
    {
        if (!$this->filesystem->exists($path)) {
            $this->logger->debug(
                sprintf('Le fichier %s n\'existe pas, abandon du traitement', $path)
            );
            return;
        }

        try {
            $this->filesystem->remove($path);
            $this->logger->info(
                sprintf('Le fichier %s est supprimé', $path)
            );
        } catch (\Throwable $exception) {
            throw new FileDeletionException($exception->getMessage(), $exception);
        }

        $this->deleteDirectoryIfEmpty($path);
    }

    private function deleteDirectoryIfEmpty(string $path): void
    {
        $dirName = dirname($path);
        $dirIsNotWorkDir = basename($dirName) !== basename($this->localPathPrefix);

        if ($dirIsNotWorkDir) {
            if (count(scandir($dirName)) == 2) {
                rmdir($dirName);
            }
        }
    }
}
