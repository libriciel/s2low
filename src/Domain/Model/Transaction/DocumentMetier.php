<?php

namespace S2low\Domain\Model\Transaction;

use S2low\Domain\Exception\VirusDetectedException;
use S2low\Domain\Model\Transaction\DTO\DocumentMetierPersistenceDTO;
use S2low\Domain\Port\AntivirusFilesScannerInterface;

class DocumentMetier
{
    private string $path;
    private bool $hasVirus;
    private bool $isReadByUser;

    /**
     * @param string $path
     * @param bool $hasVirus
     * @param bool $isReadByUser
     */
    public function __construct(string $path, bool $hasVirus, bool $isReadByUser)
    {
        $this->path = $path;
        $this->hasVirus = $hasVirus;
        $this->isReadByUser = $isReadByUser;
    }

    public function markInfected(): void
    {
        $this->hasVirus = true;
    }

    /**
     * @param AntivirusFilesScannerInterface $scanner
     * @throws VirusDetectedException
     */
    public function scanWith(AntivirusFilesScannerInterface $scanner): void
    {
        $scanner->scan($this->path);
    }

    public function toPersistenceDto(): DocumentMetierPersistenceDTO
    {
        return new DocumentMetierPersistenceDTO(
            $this->path,
            $this->hasVirus,
            $this->isReadByUser,
        );
    }
}