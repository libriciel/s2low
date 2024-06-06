<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\ICloudStorable;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PESAllerCloudStorage implements ICloudStorable
{
    public const CONTAINER_NAME = 'pes_aller';
    private HeliosTransactionsSQL $transactionsSQL;
    private string $helios_files_upload_root;

    public function __construct(string $helios_files_upload_root, HeliosTransactionsSQL $transactionsSQL)
    {
        $this->helios_files_upload_root = $helios_files_upload_root;
        $this->transactionsSQL = $transactionsSQL;
    }

    public function getContainerName(): string
    {
        return self::CONTAINER_NAME;
    }

    public function getAllObjectIdToStore(): array
    {
        return $this->transactionsSQL->getAllTransactionIdToSendInCloud();
    }

    public function getFilePathOnDisk(int $object_id): string
    {
        $transaction_info = $this->transactionsSQL->getInfo($object_id);
        return $this->helios_files_upload_root . '/' . $transaction_info['sha1'];
    }

    public function getFilePathOnCloud(int $object_id): string
    {
        return $this->transactionsSQL->getInfo($object_id)['sha1'];
    }

    public function getFilePathOnCloudWithFileOnDiskPath(string $file_on_disk_path): string
    {
        return $this->transactionsSQL->getIdBySHA1($file_on_disk_path);
    }

    public function setNotAvailable(int $object_id): void
    {
        $this->transactionsSQL->setTransactionNotAvailable($object_id);
    }

    public function setInCloud(int $object_id, bool $inCloud = true): void
    {
        $this->transactionsSQL->setTransactionInCloud($object_id, $inCloud);
    }

    public function getFinder(): Finder
    {
        $finder = new Finder();
        $finder->in($this->helios_files_upload_root);
        return $finder;
    }

    public function deleteFileOnDisk(SplFileInfo $file): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($file->getRealPath());
    }

    public function getObjectIdByFilePath(string $filepath): int
    {
        return $this->transactionsSQL->getIdBySHA1(basename($filepath));
    }

    public function setAvailable(int $object_id, bool $available): void
    {
        $this->transactionsSQL->setTransactionAvailable($object_id, $available);
    }

    public function isAvailable(int $object_id): bool
    {
        return $this->transactionsSQL->isTransactionAvailable($object_id);
    }

    public function isTransactionInCloud(int $object_id)
    {
        return $this->transactionsSQL->isTransactionInCloud($object_id);
    }
}
