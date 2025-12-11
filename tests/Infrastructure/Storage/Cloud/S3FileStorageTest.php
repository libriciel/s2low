<?php

declare(strict_types=1);

namespace S2low\Tests\Infrastructure\Storage\Cloud;

use Aws\Command;
use Aws\Exception\AwsException;
use Aws\S3\S3ClientInterface;
use PHPUnit\Framework\TestCase;
use S2low\Infrastructure\Storage\Cloud\S3FileStorage;
use S2lowLegacy\Class\CloudStorageException;

/**
 * @covers \S2low\Infrastructure\Storage\Cloud\S3FileStorage
 */
final class S3FileStorageTest extends TestCase
{
    private string $bucket = 'test-bucket';

    private function createS3ClientMock(array $methodResponses = []): S3ClientInterface
    {
        $s3Client = $this->createMock(S3ClientInterface::class);

        $s3Client
            ->method('__call')
            ->willReturnCallback(function ($method, $args) use ($methodResponses) {
                if (isset($methodResponses[$method])) {
                    $response = $methodResponses[$method];
                    if ($response instanceof \Throwable) {
                        throw $response;
                    }
                    return $response;
                }
                return null;
            });

        return $s3Client;
    }

    /**
     * Vérifie que l'upload d'un fichier vers S3 fonctionne correctement
     */
    public function testUploadFileSuccess(): void
    {
        $s3Client = $this->createS3ClientMock(['putObject' => null]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        // Ne doit pas lancer d'exception
        $s3FileStorage->uploadFile('/path/to/local/file.pdf', 'cloud-id-123');
        $this->assertTrue(true);
    }

    /**
     * Vérifie qu'une CloudStorageException est levée en cas d'erreur lors de l'upload
     */
    public function testUploadFileThrowsCloudStorageExceptionOnError(): void
    {
        $s3Client = $this->createS3ClientMock([
            'putObject' => new \Exception('S3 error')
        ]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('S3 error');

        $s3FileStorage->uploadFile('/path/to/local/file.pdf', 'cloud-id-123');
    }

    /**
     * Vérifie que le téléchargement d'un fichier depuis S3 fonctionne correctement
     */
    public function testDownloadFileSuccess(): void
    {
        $s3Client = $this->createS3ClientMock(['getObject' => null]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        // Ne doit pas lancer d'exception
        $s3FileStorage->downloadFile('/path/to/local/file.pdf', 'cloud-id-123');
        $this->assertTrue(true);
    }

    /**
     * Vérifie qu'une CloudStorageException est levée en cas d'erreur lors du téléchargement
     */
    public function testDownloadFileThrowsCloudStorageExceptionOnError(): void
    {
        $s3Client = $this->createS3ClientMock([
            'getObject' => new \Exception('Download error')
        ]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Download error');

        $s3FileStorage->downloadFile('/path/to/local/file.pdf', 'cloud-id-123');
    }

    /**
     * Vérifie que la suppression d'un fichier sur S3 fonctionne correctement
     */
    public function testDeleteFileSuccess(): void
    {
        $s3Client = $this->createS3ClientMock(['deleteObject' => null]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        // Ne doit pas lancer d'exception
        $s3FileStorage->deleteFile('cloud-id-123');
        $this->assertTrue(true);
    }

    /**
     * Vérifie qu'une CloudStorageException est levée en cas d'erreur lors de la suppression
     */
    public function testDeleteFileThrowsCloudStorageExceptionOnError(): void
    {
        $s3Client = $this->createS3ClientMock([
            'deleteObject' => new \Exception('Delete error')
        ]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Delete error');

        $s3FileStorage->deleteFile('cloud-id-123');
    }

    /**
     * Vérifie que fileExists retourne true quand le fichier existe sur S3
     */
    public function testFileExistsReturnsTrue(): void
    {
        $s3Client = $this->createS3ClientMock(['headObject' => []]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        $result = $s3FileStorage->fileExists('cloud-id-123');

        $this->assertTrue($result);
    }

    /**
     * Vérifie que fileExists retourne false quand S3 retourne une erreur 404
     */
    public function testFileExistsReturnsFalseOn404(): void
    {
        $command = $this->createMock(Command::class);
        $awsException = new AwsException('Not Found', $command, [
            'code' => 'NotFound',
            'response' => new \GuzzleHttp\Psr7\Response(404),
        ]);

        $s3Client = $this->createS3ClientMock(['headObject' => $awsException]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        $result = $s3FileStorage->fileExists('cloud-id-123');

        $this->assertFalse($result);
    }

    /**
     * Vérifie que fileExists retourne false quand S3 retourne une erreur NoSuchKey
     */
    public function testFileExistsReturnsFalseOnNoSuchKey(): void
    {
        $command = $this->createMock(Command::class);
        $awsException = new AwsException('No such key', $command, [
            'code' => 'NoSuchKey',
        ]);

        $s3Client = $this->createS3ClientMock(['headObject' => $awsException]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        $result = $s3FileStorage->fileExists('cloud-id-123');

        $this->assertFalse($result);
    }

    /**
     * Vérifie qu'une CloudStorageException est levée pour les autres erreurs AWS (ex: AccessDenied)
     */
    public function testFileExistsThrowsCloudStorageExceptionOnOtherAwsError(): void
    {
        $command = $this->createMock(Command::class);
        $awsException = new AwsException('Access Denied', $command, [
            'code' => 'AccessDenied',
            'response' => new \GuzzleHttp\Psr7\Response(403),
        ]);

        $s3Client = $this->createS3ClientMock(['headObject' => $awsException]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Access Denied');

        $s3FileStorage->fileExists('cloud-id-123');
    }

    /**
     * Vérifie qu'une CloudStorageException est levée pour les erreurs génériques
     */
    public function testFileExistsThrowsCloudStorageExceptionOnGenericError(): void
    {
        $s3Client = $this->createS3ClientMock([
            'headObject' => new \Exception('Generic error')
        ]);
        $s3FileStorage = new S3FileStorage($this->bucket, $s3Client);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Generic error');

        $s3FileStorage->fileExists('cloud-id-123');
    }
}
