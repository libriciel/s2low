<?php

namespace S2low\Services\XMLFromDGFiP;

use S2low\DTO\PesEntrantHandlingResult;
use S2low\Infrastructure\FileMetadata;
use S2low\ProcessingResults\CreateOcreFile;
use SplFileObject;

class IncomingOcreFileStrategy implements IncomingFileHandlingStrategy
{
    public function __construct(
        private readonly CreateOcreFile $createOcreFile,
    ) {
    }

    public function canHandle(FileMetadata $fileMetadata): bool
    {
        return $fileMetadata->hasExtension('ocre');
    }

    public function handle(FileMetadata $fileMetadata, SplFileObject $fileObject): PesEntrantHandlingResult
    {
        $this->createOcreFile->execute($fileObject);
        return new PesEntrantHandlingResult();
    }
}
