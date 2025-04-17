<?php

namespace S2lowLegacy\Cloud;

use Exception;
use S2lowLegacy\Model\HeliosRetourSQL;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PESRetourCloudStorage implements ICloudStorable
{
    public const CONTAINER_NAME = "helios_pes_retour";

    private $heliosRetourSQL;
    private $helios_responses_root;

    public function __construct(
        HeliosRetourSQL $heliosRetourSQL,
        $helios_responses_root
    ) {
        $this->heliosRetourSQL = $heliosRetourSQL;
        $this->helios_responses_root = $helios_responses_root;
    }

    public function getContainerName(): string
    {
        return self::CONTAINER_NAME;
    }

    public function getAllObjectIdToStore(): array
    {
        return $this->heliosRetourSQL->getAllIdPESRetourToSendInCloud();
    }

    public function getFilePathOnDisk(int $object_id): string
    {
        $transaction_info = $this->heliosRetourSQL->getInfo($object_id);
        if (empty($transaction_info['filename'])) {
            return "";
        }
        return sprintf("%s/%s", $this->helios_responses_root, $transaction_info['filename']);
    }

    public function getFilePathOnCloud(int $object_id): string
    {
        $transaction_info = $this->heliosRetourSQL->getInfo($object_id);
        if (empty($transaction_info['filename'])) {
            return "";
        }
        return $transaction_info['filename'];
    }

    public function getFilePathOnCloudWithFileOnDiskPath(string $file_on_disk_path): string
    {
        return basename($file_on_disk_path);
    }

    public function setNotAvailable(int $object_id): void
    {
        $this->heliosRetourSQL->setPesRetourNotAvailable($object_id);
    }

    public function setInCloud(int $object_id, bool $inCloud = true): void
    {
        $this->heliosRetourSQL->setPesRetourInCloud($object_id, $inCloud);
    }

    public function getFinder(): Finder
    {
        $finder = new Finder();
        $finder->in($this->helios_responses_root)->name("PES2R*.xml");
        return $finder;
    }

    public function deleteFileOnDisk(\SplFileInfo $file): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($file->getRealPath());
    }

    public function getObjectIdByFilePath(string $filepath): int
    {
        return $this->heliosRetourSQL->getByFilename(
            $this->getFilePathOnCloudWithFileOnDiskPath($filepath)
        );
    }

    public function setAvailable(int $object_id, bool $available = true): void
    {
        $this->heliosRetourSQL->setAvailable($object_id, $available);
    }

    public function isAvailable(int $object_id): bool
    {
        return $this->heliosRetourSQL->isAvailable($object_id);
    }

    public function isTransactionInCloud(int $object_id): bool
    {
        return $this->heliosRetourSQL->isInCloud($object_id);
    }

    public function getDirectoryForFilesWithoutTransaction(): ?string
    {
        return null;
    }

    public function getDesiredPathInDirectoryForFilesWithoutTransaction(SplFileInfo $file): string
    {
        throw new Exception('Not implemented yet');
    }
}
