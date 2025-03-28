<?php

namespace S2low\Domain\Model\Transaction\DTO;

use S2low\Domain\Model\Transaction\DocumentMetier;

class DocumentMetierPersistenceDTO
{
    public function __construct(
        public readonly string $path,
        public readonly bool $hasVirus,
        public readonly bool $isReadByUser,
        public readonly bool $antivirusChecked,
        public readonly string $prefix,
    ) {
    }

    public function toModel(): DocumentMetier
    {
        return new DocumentMetier(
            $this->path,
            $this->hasVirus,
            $this->isReadByUser,
            $this->antivirusChecked,
            $this->prefix
        );
    }
}
