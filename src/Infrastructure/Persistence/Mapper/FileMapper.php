<?php

namespace S2low\Infrastructure\Persistence\Mapper;

use phpseclib3\Exception\FileNotFouxndException;
use Psr\Log\LoggerInterface;
use S2low\Domain\Contract\CloudStorageInterface;
use S2low\Domain\Exception\CloudStorageDownloadException;
use S2low\Domain\Exception\CloudStorageFileNotFoundException;
use S2low\Infrastructure\Adapter\Enum\BucketName;
use Symfony\Component\HttpFoundation\File\File;

class FileMapper
{
    public function __construct(
        private readonly string $acteFilesUploadRoot,
        private readonly CloudStorageInterface $cloudStorage,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function mapFromPath(string $path): File
    {
        $localPath = $this->acteFilesUploadRoot . "/" . $path;

        if (!file_exists($localPath)) {
            try {
                $this->cloudStorage->downloadToLocalPathDestination(BucketName::ACTE_ENVELOPPE, $path, $localPath);
            } catch (CloudStorageDownloadException $e) {
                $this->logger->error($e->getMessage());
                throw $e;
            } catch (CloudStorageFileNotFoundException $e) {
                $this->logger->error($e->getMessage());
                throw $e;
                // TODO faire 2 traitements disctincts ou merge les deux
            }
        }

        return new File($localPath);
    }
}