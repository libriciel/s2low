<?php

namespace App\Factory;

use App\CloudAccess\OldS3;

class OldS3ClientFactory
{
    public static function getClient(
        string $endpoint,
        string $region,
        string $accessKey,
        string $secretKey,
    ): OldS3
    {
        return new OldS3(
            $endpoint,
            $region,
            $accessKey,
            $secretKey,
        );
    }
}
