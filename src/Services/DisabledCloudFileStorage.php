<?php

namespace S2low\Services;

class DisabledCloudFileStorage implements CloudFileStorageInterface
{
    public function storeFileOnCloud(string $transactionId): void
    {
        // Cloud Désactivé
    }

    public function downloadFileFromCloud(string $transactionId): void
    {
        // Cloud Désactivé
    }

    public function deleteFileFromCloud(string $transactionId): void
    {
        // Cloud Désactivé
    }

    public function fileExistOnCloud(string $transactionId): bool
    {
        // Cloud Désactivé && Dumb Methode
        return false;
    }
}
