<?php

namespace S2low\Factory;

use S2low\Port\CloudClientInterface;
use S2low\Services\CloudFileStorage;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\DisabledCloudFileStorage;
use S2low\Services\FileDataProvider;
use S2low\Services\LocalFileResolver;

class CloudFileStorageFactory
{
    public function __construct(
        private readonly bool $cloudStorageEnabled,
    ) {
    }

    public function create(
        CloudClientInterface $clientCloudStorage,
        LocalFileResolver $localFileResolver,
        FileDataProvider $fileDataProvider,
    ): CloudFileStorageInterface {
        if ($this->cloudStorageEnabled) {
            $fileStorage = new CloudFileStorage(
                $clientCloudStorage,
                $localFileResolver,
                $fileDataProvider,
            );
        } else {
            $fileStorage = new DisabledCloudFileStorage();
        }

        return $fileStorage;
    }
}
