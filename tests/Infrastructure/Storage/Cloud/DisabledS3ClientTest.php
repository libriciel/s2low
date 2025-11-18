<?php

declare(strict_types=1);

namespace S2low\Tests\Infrastructure\Storage\Cloud;

use PHPUnit\Framework\TestCase;
use S2low\Infrastructure\Storage\Cloud\DisabledS3Client;
use S2low\Port\CloudClientInterface;

/**
 * @covers \S2low\Infrastructure\Storage\Cloud\DisabledS3Client
 */
final class DisabledS3ClientTest extends TestCase
{
    private DisabledS3Client $disabledClient;

    protected function setUp(): void
    {
        $this->disabledClient = new DisabledS3Client();
    }

    /**
     * Vérifie que la classe implémente l'interface CloudClientInterface
     */
    public function testImplementsCloudClientInterface(): void
    {
        $this->assertInstanceOf(CloudClientInterface::class, $this->disabledClient);
    }

    /**
     * Vérifie que uploadFile ne fait rien quand le stockage cloud est désactivé
     */
    public function testUploadFileDoesNothing(): void
    {
        // Ne doit pas lancer d'exception
        $this->disabledClient->uploadFile('/path/to/file', 'cloud-id');
        $this->assertTrue(true); // Vérifie qu'on a atteint ce point
    }

    /**
     * Vérifie que downloadFile ne fait rien quand le stockage cloud est désactivé
     */
    public function testDownloadFileDoesNothing(): void
    {
        // Ne doit pas lancer d'exception
        $this->disabledClient->downloadFile('/path/to/file', 'cloud-id');
        $this->assertTrue(true);
    }

    /**
     * Vérifie que deleteFile ne fait rien quand le stockage cloud est désactivé
     */
    public function testDeleteFileDoesNothing(): void
    {
        // Ne doit pas lancer d'exception
        $this->disabledClient->deleteFile('cloud-id');
        $this->assertTrue(true);
    }

    /**
     * Vérifie que fileExists retourne toujours false quand le stockage cloud est désactivé
     */
    public function testFileExistsReturnsFalse(): void
    {
        $result = $this->disabledClient->fileExists('cloud-id');

        $this->assertFalse($result);
    }
}
