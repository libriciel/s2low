<?php

namespace Tests;

use App\SourceStorage;
use Aws\Result;
use Aws\MockHandler;
use PHPUnit\Framework\TestCase;

class SourceStorageTest extends TestCase
{
    public function testExistsInOldS3()
    {
        // Mock S3 successful HEAD
        $mock = new MockHandler();
        $mock->append(new Result()); // 200 OK for HeadObject

        $config = [
            'OLD_S3_REGION' => 'r',
            'OLD_S3_ENDPOINT' => 'https://example.com',
            'OLD_S3_ACCESS_KEY' => 'k',
            'OLD_S3_SECRET_KEY' => 's',
            'handler' => $mock
        ];

        // Use anonymous class to override getBucket
        $storage = new class ($config) extends SourceStorage {
            public function getBucket(string $type, string $filename): string
            {
                return 'test-bucket';
            }
        };

        // First call should find it in OldS3
        $this->assertEquals('OldS3', $storage->exists('some-key', 'actes'));
    }
}
