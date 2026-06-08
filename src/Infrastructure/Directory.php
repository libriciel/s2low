<?php

namespace S2low\Infrastructure;

use Psr\Log\LoggerInterface;
use RuntimeException;
use S2low\Exceptions\MoveFileException;
use SplFileObject;
use Symfony\Component\Filesystem\Path;

class Directory
{
    public function __construct(
        private readonly string $path
    ) {
    }
    public function getPath(?string $filename = null): string
    {
        $filename = $filename ?? '';
        return Path::join($this->path, $filename);
    }
}
