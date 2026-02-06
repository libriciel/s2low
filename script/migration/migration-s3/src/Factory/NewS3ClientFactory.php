<?php

namespace App\Factory;

use App\CloudAccess\NewS3;

class NewS3ClientFactory
{
    public static function getClient(
        $endpoint,
        string $region,
        string $accessKey,
        string $secretKey,
        array $options = []
    ): NewS3 {
        return new NewS3(
            $endpoint,
            $region,
            $accessKey,
            $secretKey,
            $options
        );
    }
}
