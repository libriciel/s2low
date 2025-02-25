<?php

namespace S2low\Domain\Contract;

use S2low\Domain\Exception\CloudStorageDownloadException;
use S2low\Domain\Model\ValueObject\DownloadResult;

interface CloudStorageInterface
{
    /**
     * @param string $bucketName
     * @param string $remoteFilePath
     * @param string $localPathDestination
     * @return DownloadResult
     * @throws CloudStorageDownloadException
     */
    public function downloadToLocalPathDestination(string $bucketName, string $remoteFilePath, string $localPathDestination): DownloadResult;
}