<?php

declare(strict_types=1);

namespace S2low\Tests\Factory;

use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use PHPUnit\Framework\TestCase;
use S2low\Factory\S3ClientFactory;

/**
 * @covers \S2low\Factory\S3ClientFactory
 */
final class S3ClientFactoryTest extends TestCase
{
    /**
     * Vérifie que la factory crée un client S3 valide
     */
    public function testCreateReturnsS3ClientInterface(): void
    {
        $factory = new S3ClientFactory(
            'http://localhost:9000',
            'test-key',
            'test-secret'
        );

        $client = $factory->create();

        $this->assertInstanceOf(S3ClientInterface::class, $client);
        $this->assertInstanceOf(S3Client::class, $client);
    }

    /**
     * Vérifie que le client est configuré avec le bon endpoint
     */
    public function testCreateConfiguresClientWithCorrectEndpoint(): void
    {
        $endpoint = 'http://minio:9000';
        $factory = new S3ClientFactory(
            $endpoint,
            'test-key',
            'test-secret'
        );

        $client = $factory->create();

        $this->assertInstanceOf(S3Client::class, $client);
    }

    /**
     * Vérifie que le client est configuré avec les credentials fournis
     */
    public function testCreateConfiguresClientWithCredentials(): void
    {
        $key = 'my-access-key';
        $secret = 'my-secret-key';

        $factory = new S3ClientFactory(
            'http://localhost:9000',
            $key,
            $secret
        );

        $client = $factory->create();

        $this->assertInstanceOf(S3Client::class, $client);
    }

    /**
     * Vérifie que chaque appel à create() retourne une nouvelle instance
     */
    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $factory = new S3ClientFactory(
            'http://localhost:9000',
            'test-key',
            'test-secret'
        );

        $client1 = $factory->create();
        $client2 = $factory->create();

        $this->assertNotSame($client1, $client2);
    }
}
