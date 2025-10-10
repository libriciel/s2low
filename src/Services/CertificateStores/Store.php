<?php

namespace S2low\Services\CertificateStores;

class Store
{
    public function __construct(
        private readonly string $path,
        private readonly Type $type
    ) {
    }

    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
