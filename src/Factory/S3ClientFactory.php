<?php

declare(strict_types=1);

namespace S2low\Factory;

use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;

class S3ClientFactory
{
    public function __construct(
        private readonly string $endpoint,
        private readonly string $key,
        private readonly string $secret
    ) {
    }

    public function create(): S3ClientInterface
    {
        return new S3Client([
            'version' => 'latest',
            'region' => 'region',
            'endpoint' => $this->endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => $this->key,
                'secret' => $this->secret,
            ],
        ]);
    }
}
