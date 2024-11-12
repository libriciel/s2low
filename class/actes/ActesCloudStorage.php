<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use S2lowLegacy\Class\ICloudStorable;
use S2lowLegacy\Lib\UnrecoverableException;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ActesCloudStorage implements ICloudStorable
{
    public const CONTAINER_NAME = 'acte_envelope';


    /**
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function __construct(
        private string $actes_files_upload_root,
        private ActesEnvelopeSQL $actesEnvelopeSQL,
        private string $repertoireActesEnveloppeSansTransaction
    ) {
        if (!is_dir($actes_files_upload_root)) {
            throw new UnrecoverableException(
                "The directory '$actes_files_upload_root' does not exist"
            );
        }
        if (!is_dir($repertoireActesEnveloppeSansTransaction)) {
            throw new UnrecoverableException(
                "The directory '$repertoireActesEnveloppeSansTransaction' does not exist"
            );
        }
    }

    public function getContainerName(): string
    {
        return self::CONTAINER_NAME;
    }

    public function getAllObjectIdToStore(): array
    {
        return $this->actesEnvelopeSQL->getAllEnvelopepIdToSendInCloud();
    }

    public function getFilePathOnDisk(int $object_id): string
    {
        $envelope_info = $this->actesEnvelopeSQL->getInfo($object_id);
        return $this->actes_files_upload_root . '/' . $envelope_info['file_path'];
    }

    public function getFilePathOnCloud(int $object_id): string
    {
        $envelope_info = $this->actesEnvelopeSQL->getInfo($object_id);
        return $envelope_info['file_path'];
    }

    /**
     * @param string $file_on_disk_path
     * @return string
     * @throws UnrecoverableException
     */
    public function getFilePathOnCloudWithFileOnDiskPath(string $file_on_disk_path): string
    {
        $actes_root = rtrim($this->actes_files_upload_root, '/');
        if (! preg_match("#$actes_root/(.*)#", $file_on_disk_path, $matches) || ! $matches[1]) {
            throw new UnrecoverableException("$file_on_disk_path doesn't match pattern $actes_root/(.*)");
        }
        return $matches[1];
    }

    public function setNotAvailable(int $object_id): void
    {
        $this->actesEnvelopeSQL->setEnveloppeNotAvailable($object_id);
    }

    public function setInCloud(int $object_id, bool $inCloud = true): void
    {
        $this->actesEnvelopeSQL->setTransactionInCloud($object_id, $inCloud);
    }

    public function getFinder(): Finder
    {
        $finder = new Finder();
        $finder->in($this->actes_files_upload_root)->name('*.tar.gz');
        return $finder;
    }

    public function deleteFileOnDisk(SplFileInfo $file): void
    {
        $filesystem = new Filesystem();
        $dirname = $file->getPath();
        $filesystem->remove($file->getRealPath());
        if (count(scandir($dirname)) == 2) {
            rmdir($dirname);
        }
    }

    /**
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function getObjectIdByFilePath(string $filepath): int
    {
        $filepath = $this->getFilePathOnCloudWithFileOnDiskPath($filepath);
        return $this->actesEnvelopeSQL->getByFilepath($filepath);
    }

    public function setAvailable(int $object_id, bool $available = true): void
    {
        $this->actesEnvelopeSQL->setAvailable($object_id, $available);
    }

    public function isAvailable(int $object_id): bool
    {
        return $this->actesEnvelopeSQL->isAvailable($object_id);
    }

    public function isTransactionInCloud(int $object_id): bool
    {
        return $this->actesEnvelopeSQL->isInCloud($object_id);
    }

    public function getDirectoryForFilesWithoutTransaction(): ?string
    {
        return $this->repertoireActesEnveloppeSansTransaction;
    }

    /**
     * @throws \Exception
     */
    public function getDesiredPathInDirectoryForFilesWithoutTransaction(SplFileInfo $file): string
    {
        return $file->getFilename();
    }
}
