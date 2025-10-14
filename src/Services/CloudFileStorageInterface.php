<?php

namespace S2low\Services;

use phpseclib3\Exception\FileNotFoundException;
use S2low\Exceptions\CloudDownloadException;
use S2low\Exceptions\CloudFileDeletionException;
use S2low\Exceptions\CloudFileUploadException;
use S2low\Exceptions\TransactionNotFoundException;

interface CloudFileStorageInterface
{
    /**
     * @throws TransactionNotFoundException
     * @throws FileNotFoundException
     * @throws CloudFileUploadException
     */
    public function storeFileOnCloud(string $transactionId): void;

    /**
     * @throws TransactionNotFoundException
     * @throws CloudDownloadException
     */
    public function downloadFileFromCloud(string $transactionId): void;

    /**
     * @throws TransactionNotFoundException
     * @throws CloudFileDeletionException
     */
    public function deleteFileFromCloud(string $transactionId): void;

    /**
     * @throws TransactionNotFoundException
     */
    public function fileExistOnCloud(string $transactionId): bool;
}
