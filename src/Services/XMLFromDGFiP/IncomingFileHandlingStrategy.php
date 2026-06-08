<?php

namespace S2low\Services\XMLFromDGFiP;

use S2low\DTO\PesEntrantHandlingResult;
use S2low\Infrastructure\FileMetadata;
use SplFileObject;

interface IncomingFileHandlingStrategy
{
    public function canHandle(FileMetadata $fileMetadata): bool;

    /**
     * @throws \S2low\Exceptions\PesEntrantSavingException
     * @throws \Throwable
     */
    public function handle(FileMetadata $fileMetadata, SplFileObject $fileObject): PesEntrantHandlingResult;
}
