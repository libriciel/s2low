<?php

namespace S2low\DomainModel\Certificates;

use DateTime;

class Revocation
{
    public function __construct(
        private readonly string $hash,
        private readonly DateTime $revocationDate
    ) {
    }

    public function appliesTo(string $hash, DateTime $time): bool
    {
        if ($hash !== $this->hash) {
            return false;
        }

        if ($time < $this->revocationDate) {
            return false;
        }
        return true;
    }
}
