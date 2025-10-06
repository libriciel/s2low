<?php

declare(strict_types=1);

namespace S2low\Infrastructure\Storage\Cloud;

use Aws\Exception\AwsException;
use Aws\S3\S3ClientInterface;
use phpseclib3\Exception\FileNotFoundException;
use S2low\Port\CloudClientInterface;
use S2lowLegacy\Class\CloudStorageException;

/**
 * @description Cette classe permet d'interagir avec un client S3 pour diverses operation sur des fichiers metiers prédeterminés.
 * Pour determiner un fichier metier spécifique, il faut utiliser l'identifiant associé à la bonne configuration. (Se referer au fichier services.yaml)
 */
class S3FileStorage implements CloudClientInterface
{
    public function __construct(
        private readonly string $bucket,
        private readonly S3ClientInterface $client,
    ) {
    }

    /**
     * @throws CloudStorageException
     * @throws FileNotFoundException
     */
    public function uploadFile(string $localFilePath, string $cloudId): void
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $cloudId,
                'SourceFile' => $localFilePath,
            ]);
        } catch (\Throwable $e) {
            throw new CloudStorageException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @throws CloudStorageException */
    public function downloadFile(string $localFilePath, string $cloudId): void
    {
        try {
            $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $cloudId,
                'SaveAs' => $localFilePath,
            ]);
        } catch (\Throwable $e) {
            throw new CloudStorageException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @throws CloudStorageException */
    public function deleteFile(string $cloudId): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $cloudId,
            ]);
        } catch (\Throwable $e) {
            throw new CloudStorageException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** @throws CloudStorageException */
    public function fileExists(string $cloudId): bool
    {
        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $cloudId,
            ]);

            return true;
        } catch (AwsException $e) {
            $statusCode = $e->getStatusCode();
            $errorCode = $e->getAwsErrorCode();

            if ($statusCode === 404 || $errorCode === 'NoSuchKey' || $errorCode === 'NotFound') {
                return false;
            }

            throw new CloudStorageException($e->getMessage(), $statusCode ?? $e->getCode(), $e);
        } catch (\Throwable $e) {
            throw new CloudStorageException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
