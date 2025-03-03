<?php

namespace S2low\Domain\Model\Transaction;

use S2low\Domain\Exception\DocumentMetierNotFoundException;
use S2low\Domain\Model\Transaction\DTO\DocumentMetierPersistenceDTO;

class DocumentMetier
{
    private string $path;
    private bool $hasVirus;
    private bool $isReadByUser;
    private bool $antivirusChecked;
    private string $prefix;

    /**
     * @param string $path
     * @param bool $hasVirus
     * @param bool $isReadByUser
     * @param bool $antivirusChecked
     * @param string $prefix
     */
    public function __construct(string $path, bool $hasVirus, bool $isReadByUser, bool $antivirusChecked, string $prefix)
    {
        $this->path = $path;
        $this->hasVirus = $hasVirus;
        $this->isReadByUser = $isReadByUser;
        $this->antivirusChecked = $antivirusChecked;
        $this->prefix = $prefix;
    }

    public function markInfected(): void
    {
        $this->hasVirus = true;
    }

    public function toPersistenceDto(): DocumentMetierPersistenceDTO
    {
        return new DocumentMetierPersistenceDTO(
            $this->path,
            $this->hasVirus,
            $this->isReadByUser,
            $this->antivirusChecked,
            $this->prefix
        );
    }

    public function assertFileIsValid(): void
    {
        if (!file_exists($this->getAbsolutePath())){
            throw new DocumentMetierNotFoundException($this->path);
        }
    }

    public function getAbsolutePath(): string
    {
        return $this->prefix .  "/" . $this->path;
    }

    public function antivirusCheck(): void
    {
        $this->antivirusChecked = true;
    }
}