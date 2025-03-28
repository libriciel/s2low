<?php

namespace S2low\Infrastructure\Adapter;

use Psr\Log\LoggerInterface;
use S2low\Domain\Exception\CloudStorageDownloadException;
use S2low\Domain\Exception\CreateNewFileException;
use S2low\Domain\Port\CloudStorageDownloaderInterface;

class OpenStackFileDownloader implements CloudStorageDownloaderInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly OpenStackAdapter $openStackAdapter,
    ) {
    }

    /**
     * @param string $containerName
     * @param string $remoteFilePath
     * @param string $localPathDestination
     * @return void
     */
    public function downloadFile(string $containerName, string $remoteFilePath, string $localPathDestination): void
    {
        try {
            $this->openStackAdapter->config($containerName);
            $this->openStackAdapter->download($remoteFilePath, $localPathDestination);

            $this->logger->info("Fichier '$localPathDestination' téléchargé avec succès.");
        } catch (CreateNewFileException | CloudStorageDownloadException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
