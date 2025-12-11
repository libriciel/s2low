<?php

declare(strict_types=1);

namespace S2low\Tests\Factory;

use PHPUnit\Framework\TestCase;
use S2low\Factory\CloudFileStorageFactory;
use S2low\Port\CloudClientInterface;
use S2low\Services\CloudFileStorage;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\DisabledCloudFileStorage;
use S2low\Services\FileDataProvider;
use S2low\Services\LocalFileResolver;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \S2low\Factory\CloudFileStorageFactory
 */
final class CloudFileStorageFactoryTest extends TestCase
{
    private const CLOUD_STORAGE_ENABLED = true;
    private const CLOUD_STORAGE_DISABLED = false;

    /**
     * Vérifie que la factory retourne une instance CloudFileStorage quand le stockage cloud est activé
     */
    public function testCreateReturnsCloudFileStorageWhenEnabled(): void
    {
        $filesystem = new Filesystem();
        $factory = new CloudFileStorageFactory($filesystem, self::CLOUD_STORAGE_ENABLED);

        $cloudClient = $this->createMock(CloudClientInterface::class);
        $localFileResolver = $this->createMock(LocalFileResolver::class);
        $fileDataProvider = $this->createMock(FileDataProvider::class);

        $result = $factory->create($cloudClient, $localFileResolver, $fileDataProvider);

        $this->assertInstanceOf(CloudFileStorageInterface::class, $result);
        $this->assertInstanceOf(CloudFileStorage::class, $result);
    }

    /**
     * Vérifie que la factory retourne une instance DisabledCloudFileStorage quand le stockage cloud est désactivé
     */
    public function testCreateReturnsDisabledCloudFileStorageWhenDisabled(): void
    {
        $filesystem = new Filesystem();
        $factory = new CloudFileStorageFactory($filesystem, self::CLOUD_STORAGE_DISABLED);

        $cloudClient = $this->createMock(CloudClientInterface::class);
        $localFileResolver = $this->createMock(LocalFileResolver::class);
        $fileDataProvider = $this->createMock(FileDataProvider::class);

        $result = $factory->create($cloudClient, $localFileResolver, $fileDataProvider);

        $this->assertInstanceOf(CloudFileStorageInterface::class, $result);
        $this->assertInstanceOf(DisabledCloudFileStorage::class, $result);
    }
}
