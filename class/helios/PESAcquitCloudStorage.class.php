<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PESAcquitCloudStorage implements ICloudStorable
{
    public const CONTAINER_NAME = "helios_pes_acquit";

    private $heliosTransactionsSQL;
    private $helios_responses_root;

    public function __construct(
        HeliosTransactionsSQL $heliosTransactionsSQL,
        $helios_responses_root
    ) {
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->helios_responses_root = $helios_responses_root;
    }

    public function getContainerName(): string
    {
        return self::CONTAINER_NAME;
    }

    public function getAllObjectIdToStore(): array
    {
        return $this->heliosTransactionsSQL->getAllIdPESAcquitToSendInCloud();
    }

    public function getFilePathOnDisk(int $object_id): string
    {
        $transaction_info = $this->heliosTransactionsSQL->getInfo($object_id);
        if (empty($transaction_info['acquit_filename'])) {
            return "";
        }
        return sprintf("%s/%s", $this->helios_responses_root, $transaction_info['acquit_filename']);
    }

    public function getFilePathOnCloud(int $object_id): string
    {
        $transaction_info = $this->heliosTransactionsSQL->getInfo($object_id);
        if (empty($transaction_info['acquit_filename'])) {
            return "";
        }
        return $transaction_info['acquit_filename'];
    }

    public function getFilePathOnCloudWithFileOnDiskPath(string $file_on_disk_path): string
    {
        return basename($file_on_disk_path);
    }

    public function setNotAvailable(int $object_id): void
    {
        $this->heliosTransactionsSQL->setPesAcquitNotAvailable($object_id);
    }

    public function setInCloud(int $object_id, bool $isInCloud = true): void
    {
        $this->heliosTransactionsSQL->setPesAcquitInCloud($object_id, $isInCloud);
    }

    public function getFinder(): Finder
    {
        $finder = new Finder();
        $finder->in($this->helios_responses_root)->name("*ACK*.xml");
        return $finder;
    }

    public function deleteFileOnDisk(SplFileInfo $file): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($file->getRealPath());
    }

    public function getObjectIdByFilePath(string $filepath): int
    {
        return $this->heliosTransactionsSQL->getByPesAcquitName(
            $this->getFilePathOnCloudWithFileOnDiskPath($filepath)
        );
    }

    public function setAvailable(int $object_id, bool $available = true): void
    {
        $this->heliosTransactionsSQL->setPesAcquitAvailable($object_id, $available);
    }

    public function isAvailable(int $object_id): bool
    {
        return $this->heliosTransactionsSQL->isPesAcquitAvailable($object_id);
    }

    public function isTransactionInCloud(int $object_id): bool
    {
        return $this->heliosTransactionsSQL->isPesAcquitInCloud($object_id);
    }
}
