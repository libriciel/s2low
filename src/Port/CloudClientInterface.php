<?php

declare(strict_types=1);

namespace S2low\Port;

use S2lowLegacy\Class\CloudStorageException;

/**
 * @description ce contrat d'interface sert a definir la manière d'interagir avec le client de stockage cloud.
 */
interface CloudClientInterface
{
    /** @throws CloudStorageException */
    public function uploadFile(string $localFilePath, string $cloudId): void;

    /** @throws CloudStorageException */
    public function downloadFile(string $localFilePath, string $cloudId): void;

    /** @throws CloudStorageException */
    public function deleteFile(string $cloudId): void;

    /** @throws CloudStorageException */
    public function fileExists(string $cloudId): bool;
}
