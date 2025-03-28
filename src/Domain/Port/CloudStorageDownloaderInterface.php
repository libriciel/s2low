<?php

namespace S2low\Domain\Port;

use S2low\Domain\Exception\CloudStorageDownloadException;

interface CloudStorageDownloaderInterface
{
    /**
     * @param string $bucketName
     * @param string $remoteFilePath
     * @param string $localPathDestination
     * @return void
     * @throws CloudStorageDownloadException
     */
    public function downloadFile(string $bucketName, string $remoteFilePath, string $localPathDestination): void;
}
