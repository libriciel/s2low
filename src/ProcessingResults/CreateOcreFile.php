<?php

namespace S2low\ProcessingResults;

use S2low\Infrastructure\Directory;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class CreateOcreFile
{
    public function __construct(
        #[Autowire(service: 'app.heliosOcreDirectory')]
        private readonly Directory $ocreDirectory,
        private readonly Filesystem $fileSystem
    ) {
    }

    /**
     */
    public function execute(SplFileObject $fileObject): void
    {
        $this->fileSystem->rename(
            $fileObject->getPathname(),
            $this->ocreDirectory->getPath($fileObject->getBasename())
        );
    }
}
