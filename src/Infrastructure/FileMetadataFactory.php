<?php

namespace S2low\Infrastructure;

use S2low\Infrastructure\XML\RootFinder\XMLRootFinder;
use SplFileObject;

class FileMetadataFactory
{
    public function __construct(
        private readonly XMLRootFinder $xmlRootFinder
    ) {
    }

    public function create(SplFileObject $file): FileMetadata
    {
        return new FileMetadata(
            $this->xmlRootFinder,
            $file,
        );
    }
}
