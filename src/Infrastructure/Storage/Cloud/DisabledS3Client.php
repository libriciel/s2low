<?php

namespace S2low\Infrastructure\Storage\Cloud;

use S2low\Port\CloudClientInterface;

class DisabledS3Client implements CloudClientInterface
{
    public function uploadFile(string $localFilePath, string $cloudId): void
    {
        // Disabled methode
    }

    public function downloadFile(string $localFilePath, string $cloudId): void
    {
        // Disabled methode
    }

    public function deleteFile(string $cloudId): void
    {
        // Disabled methode
    }

    public function fileExists(string $cloudId): bool
    {
        // Disabled
        return false;
    }
}
