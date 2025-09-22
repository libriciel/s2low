<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;

class HeliosPurge
{
    private LoggerInterface $s2lowLogger;
    /**
     * @var \S2lowLegacy\Class\helios\HeliosFilesFactory
     */
    private HeliosFilesFactory $heliosFilesFactory;

    public function __construct(
        LoggerInterface $s2lowLogger,
        HeliosFilesFactory $heliosFilesFactory
    ) {
        $this->heliosFilesFactory = $heliosFilesFactory;
        $this->s2lowLogger = $s2lowLogger;
    }

    public function purge($transaction_id)
    {
        $heliosFilesNames = $this->heliosFilesFactory->get($transaction_id);

        foreach ($heliosFilesNames->getFileNames() as $TypeDeFichierHelios => $path) {
            $this->deleteFileIfExists($path, $TypeDeFichierHelios);
        }
    }

    /**
     * @param mixed $path
     * @param string $TypeDeFIchierHelios
     * @return void
     */
    private function deleteFileIfExists(string $path, string $TypeDeFIchierHelios): void
    {
        if (file_exists($path) && is_file($path)) {
            unlink($path);
            $this->s2lowLogger->info(
                "Le fichier $TypeDeFIchierHelios {$path} a été supprimé"
            );
        }
    }
}
