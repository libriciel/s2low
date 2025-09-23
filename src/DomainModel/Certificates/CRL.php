<?php

namespace S2low\DomainModel\Certificates;

use DateTime;

class CRL
{
    /**
     * @var \S2low\DomainModel\Certificates\Revocation[]
     */
    private array $revocationList;

    public function __construct(Revocation ...$revocations)
    {
        $this->revocationList = $revocations;
    }

    public function isRevoked(string $hash, DateTime $time): bool
    {
        foreach ($this->revocationList as $revocation) {
            if ($revocation->revokes($hash, $time)) {
                return true;
            }
        }
        return false;
    }
}
