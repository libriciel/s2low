<?php

namespace S2low\Factory;

use Aws\S3\S3ClientInterface;
use S2low\Infrastructure\Storage\Cloud\DisabledS3Client;
use S2low\Infrastructure\Storage\Cloud\S3FileStorage;
use S2low\Port\CloudClientInterface;

class S3FileStorageFactory
{
    public function __construct(
        private readonly bool $cloudStorageEnabled,
    ) {
    }

    public function create(
        ?string $bucket,
        S3ClientInterface $client,
    ): CloudClientInterface {
        if ($this->cloudStorageEnabled) {
            $fileStorage = new S3FileStorage(
                $bucket,
                $client
            );
        } else {
            $fileStorage = new DisabledS3Client();
        }

        return $fileStorage;
    }
}
