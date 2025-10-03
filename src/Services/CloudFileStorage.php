<?php

declare(strict_types=1);

namespace S2low\Services;

use phpseclib3\Exception\FileNotFoundException;
use S2low\Exceptions\CloudException;
use S2low\Exceptions\CloudFileDeletionException;
use S2low\Exceptions\CloudDownloadException;
use S2low\Exceptions\CloudFileUploadException;
use S2low\Exceptions\TransactionNotFoundException;
use S2low\Port\CloudClientInterface;
use S2lowLegacy\Class\CloudStorageException;

/**
 * @description Cette classe doit etre utilisé pour interagir avec le Cloud dans S2low.
 * Elle possède plusieurs identifiants. Chacun paramétré pour un fichier metier different.
 * Par exemple pour une enveloppe Acte. Il faudra autowire BusinessFileRepository tel que :
       #[Autowire(service: 'app.store.file.acte_enveloppe')]
       private readonly BusinessFileRepository $storeActeEnveloppe
 *
 * On peut ainsi interagir avec les fichiers dans le cloud sans plus de paramétrage.
 */
class CloudFileStorage
{
    public function __construct(
        private readonly CloudClientInterface $cloudFileStorage,
        private readonly LocalFileResolver $localFileResolver,
        private readonly FileDataProvider $fileDataProvider,
    ) {
    }

    /**
     * @throws TransactionNotFoundException
     * @throws FileNotFoundException
     * @throws CloudFileUploadException
     */
    public function storeFileOnCloud(string $transactionId): void
    {
        $filePath = $this->localFileResolver->getFullPath($transactionId);
        $cloudId = $this->fileDataProvider->getCloudId($transactionId);

        if (!file_exists($filePath)) {
            throw new FileNotFoundException("Le fichier $cloudId associé à la transaction $transactionId n'est pas present sur le serveur S2low. Impossible de le stocker sur le service de stockage cloud.");
        }

        try {
            $this->cloudFileStorage->uploadFile($filePath, $cloudId);
        } catch (\Throwable $e) {
            throw new CloudFileUploadException($transactionId, $cloudId, $e);
        }
    }

    /**
     * @throws TransactionNotFoundException
     * @throws CloudDownloadException
     */
    public function downloadFileFromCloud(string $transactionId): void
    {
        $filePath = $this->localFileResolver->getFullPath($transactionId);
        $cloudId = $this->fileDataProvider->getCloudId($transactionId);

        try {
            $this->cloudFileStorage->downloadFile($filePath, $cloudId);
        } catch (\Throwable $e) {
            throw new CloudDownloadException($transactionId, $cloudId, $e);
        }
    }

    /**
     * @throws TransactionNotFoundException
     * @throws CloudFileDeletionException
     */
    public function deleteFileFromCloud(string $transactionId): void
    {
        $cloudId = $this->fileDataProvider->getCloudId($transactionId);

        try {
            $this->cloudFileStorage->deleteFile($cloudId);
        } catch (\Throwable $e) {
            throw new CloudFileDeletionException($transactionId, $cloudId, $e);
        }
    }

    /**
     * @throws TransactionNotFoundException
     */
    public function fileExistOnCloud(string $transactionId): bool
    {
        $cloudId = $this->fileDataProvider->getCloudId($transactionId);

        try {
            $fileExists = $this->cloudFileStorage->fileExists($cloudId);
        } catch (\Throwable $e) {
            throw new CloudException($transactionId, $cloudId, $e);
        }

        return $fileExists;
    }
}
