<?php

namespace App\CloudAccess;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class NewS3
{
    private S3Client $client;

    public function __construct(string $endpoint, string $region, string $accessKey, string $secretKey, array $options = [])
    {
        $config = [
            'region'  => $region,
            'version' => 'latest',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ];

        if (isset($options['handler'])) {
            $config['handler'] = $options['handler'];
        }

        $this->client = new S3Client($config);
    }

    public function getBucket(string $type, string $filename): string
    {
        return 'test-bucket';
    }

    public function upload(string $key, string $sourcePath, string $type): bool
    {
        $bucket = $this->getBucket($type, $key);
        try {
            $this->client->putObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'SourceFile' => $sourcePath,
            ]);
            return true;
        } catch (AwsException $e) {
            echo "Error uploading to NewS3: " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }

    public function exists(string $key, string $type): bool
    {
        $bucket = $this->getBucket($type, $key);
        try {
            $this->client->headObject([
               'Bucket' => $bucket,
               'Key'    => $key,
            ]);
            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    public function checkConnection(): bool
    {
        try {
            // Since bucket is dynamic, we check if we can list buckets to verify auth/connectivity
            $this->client->listBuckets();
             echo "NewS3 Connection OK (ListBuckets successful)." . PHP_EOL;
             return true;
        } catch (\Exception $e) {
            echo "NewS3 Connection Failed: " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }
}
