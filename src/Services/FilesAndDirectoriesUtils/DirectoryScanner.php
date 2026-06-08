<?php

namespace S2low\Services\FilesAndDirectoriesUtils;

use Psr\Log\LoggerInterface;
use S2low\Infrastructure\Directory;
use Symfony\Component\Finder\Finder;

class DirectoryScanner
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }
    public function getFileNames(Directory $directory): array
    {
        $path = $directory->getPath();

        $this->logger->info("Analyse du répertoire : $path");

        try {
            $files = (new Finder())->files()->in($path)->depth('== 0');
        } catch (\Throwable $throwable) {
            $this->logger->critical("[ECHEC] Erreur lors de la lecture du répertoire  $path");
            return [];
        }

        $fileNames = array_map(
            static fn(\SplFileInfo $file) => $file->getFilename(),
            iterator_to_array($files),
        );

        if ($fileNames === []) {
            $this->logger->info('Aucun fichier à analyser');
            return [];
        }
        $this->logger->info('Traitement de ' . count($fileNames) . ' fichiers trouvés');
        return $fileNames;
    }
}
