<?php

declare(strict_types=1);

namespace S2low\Tests\Factory;

use Aws\S3\S3ClientInterface;
use PHPUnit\Framework\TestCase;
use S2low\Factory\S3FileStorageFactory;
use S2low\Infrastructure\Storage\Cloud\DisabledS3Client;
use S2low\Infrastructure\Storage\Cloud\S3FileStorage;
use S2low\Port\CloudClientInterface;

/**
 * @covers \S2low\Factory\S3FileStorageFactory
 */
final class S3FileStorageFactoryTest extends TestCase
{
    private const CLOUD_STORAGE_ENABLED = true;
    private const CLOUD_STORAGE_DISABLED = false;

    /**
     * Vérifie que la factory retourne une instance S3FileStorage quand le stockage cloud est activé
     */
    public function testCreateReturnsS3FileStorageWhenEnabled(): void
    {
        $factory = new S3FileStorageFactory(self::CLOUD_STORAGE_ENABLED);
        $s3Client = $this->createMock(S3ClientInterface::class);

        $result = $factory->create('test-bucket', $s3Client);

        $this->assertInstanceOf(CloudClientInterface::class, $result);
        $this->assertInstanceOf(S3FileStorage::class, $result);
    }

    /**
     * Vérifie que la factory retourne une instance DisabledS3Client quand le stockage cloud est désactivé
     */
    public function testCreateReturnsDisabledS3ClientWhenDisabled(): void
    {
        $factory = new S3FileStorageFactory(self::CLOUD_STORAGE_DISABLED);
        $s3Client = $this->createMock(S3ClientInterface::class);

        $result = $factory->create('test-bucket', $s3Client);

        $this->assertInstanceOf(CloudClientInterface::class, $result);
        $this->assertInstanceOf(DisabledS3Client::class, $result);
    }

    /**
     * Vérifie qu'une exception est levée si le bucket est null quand le stockage cloud est activé
     */
    public function testCreateWithNullBucketWhenEnabledThrowsException(): void
    {
        $factory = new S3FileStorageFactory(self::CLOUD_STORAGE_ENABLED);
        $s3Client = $this->createMock(S3ClientInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le bucket ne peut pas être null quand le stockage cloud est activé.');

        $factory->create(null, $s3Client);
    }

    /**
     * Vérifie que la factory accepte un bucket null quand le stockage cloud est désactivé
     * (car le bucket n'est pas utilisé dans ce cas)
     */
    public function testCreateWithNullBucketWhenDisabled(): void
    {
        $factory = new S3FileStorageFactory(self::CLOUD_STORAGE_DISABLED);
        $s3Client = $this->createMock(S3ClientInterface::class);

        $result = $factory->create(null, $s3Client);

        $this->assertInstanceOf(DisabledS3Client::class, $result);
    }
}
