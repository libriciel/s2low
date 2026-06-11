<?php

namespace S2low\Services\FilesAndDirectoriesUtils;

use Psr\Log\LoggerInterface;
use S2low\Infrastructure\Directory;
use SplFileObject;
use Symfony\Component\Filesystem\Filesystem;

class FileMover
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {
    }
    /**
     * @param \SplFileObject $fileObject
     * @param \S2low\Infrastructure\Directory $destinationDirectory
     * @return void
     */
    public function moveFileWithRename(SplFileObject $fileObject, Directory $destinationDirectory): void
    {
        // Already in destination directory
        if (realpath($fileObject->getPath()) === realpath($destinationDirectory->getPath())) {
            return;
        }

        $destinationPath = $destinationDirectory->getPath($fileObject->getBasename());

        if (!file_exists($destinationPath)) {
            $this->filesystem->rename(
                $fileObject->getPathname(),
                $destinationPath
            );
            return;
        }

        $i = 0;
        do {
            $i++;
            $file_num = "$destinationPath.$i";
        } while (file_exists($file_num));
        $this->logger->warning("[WARNING] Le fichier {$fileObject->getBasename()} existe déjà dans le répertoire des fichiers en erreur : renommé en *.$i");
        rename($fileObject->getPathname(), $file_num);
    }
}
