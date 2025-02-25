<?php

namespace S2low\Infrastructure\Adapter;

use S2low\Domain\Contract\CloudStorageInterface;
use S2low\Domain\Model\ValueObject\DownloadResult;

class OpenStackStorageProvider implements CloudStorageInterface
{
    /**
     * @param string $bucketName
     * @param string $remoteFilePath
     * @param string $localPathDestination
     * @return DownloadResult
     */
    public function downloadToLocalPathDestination(string $bucketName, string $remoteFilePath, string $localPathDestination): DownloadResult
    {
        return new DownloadResult(
            true,
            $localPathDestination
        );
    }
}