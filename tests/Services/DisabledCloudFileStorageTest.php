<?php

declare(strict_types=1);

namespace S2low\Tests\Services;

use PHPUnit\Framework\TestCase;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\DisabledCloudFileStorage;

/**
 * @covers \S2low\Services\DisabledCloudFileStorage
 */
final class DisabledCloudFileStorageTest extends TestCase
{
    private DisabledCloudFileStorage $disabledStorage;

    protected function setUp(): void
    {
        $this->disabledStorage = new DisabledCloudFileStorage();
    }

    /**
     * Vérifie que la classe implémente l'interface CloudFileStorageInterface
     */
    public function testImplementsCloudFileStorageInterface(): void
    {
        $this->assertInstanceOf(CloudFileStorageInterface::class, $this->disabledStorage);
    }

    /**
     * Vérifie que storeFileOnCloud ne fait rien quand le stockage cloud est désactivé
     */
    public function testStoreFileOnCloudDoesNothing(): void
    {
        // Ne doit pas lancer d'exception
        $this->disabledStorage->storeFileOnCloud('tx-123');
        $this->assertTrue(true);
    }

    /**
     * Vérifie que downloadFileFromCloud ne fait rien quand le stockage cloud est désactivé
     */
    public function testDownloadFileFromCloudDoesNothing(): void
    {
        // Ne doit pas lancer d'exception
        $this->disabledStorage->downloadFileFromCloud('tx-123');
        $this->assertTrue(true);
    }

    /**
     * Vérifie que deleteFileFromCloud ne fait rien quand le stockage cloud est désactivé
     */
    public function testDeleteFileFromCloudDoesNothing(): void
    {
        // Ne doit pas lancer d'exception
        $this->disabledStorage->deleteFileFromCloud('tx-123');
        $this->assertTrue(true);
    }

    /**
     * Vérifie que fileExistOnCloud retourne toujours false quand le stockage cloud est désactivé
     */
    public function testFileExistOnCloudReturnsFalse(): void
    {
        $result = $this->disabledStorage->fileExistOnCloud('tx-123');

        $this->assertFalse($result);
    }
}
