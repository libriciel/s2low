<?php

namespace S2low\Services\Certificates;

use DateTime;
use DateTimeZone;
use S2low\DomainModel\Certificates\CRL;
use S2low\DomainModel\Certificates\Revocation;
use S2low\Exceptions\CrlParsingException;

class CRLReader
{
    /**
     * @throws \S2low\Exceptions\CrlParsingException
     */
    public function read(string $content): CRL
    {
        $lines = preg_split('/\R/', $content);
        $currentSerialNumber = null;
        $revocations = [];

        foreach ($lines as $crlLine) {
            $serial = $this->parseSerialNumber($crlLine);
            if ($serial !== null) {
                $currentSerialNumber = $serial;
                continue;
            }
            if ($currentSerialNumber === null) {
                continue;
            }
            $revocationDate = $this->parseRevocation($crlLine);
            if ($revocationDate !== null) {
                $revocations[] = new Revocation($currentSerialNumber, $revocationDate);
                $currentSerialNumber = null;
            }
        }
        return new CRL(...$revocations);
    }

    private function parseSerialNumber(string $line): ?string
    {
        if (preg_match('/^\s*Serial Number:\s*([0-9A-F]+)/i', $line, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * @throws \S2low\Exceptions\CrlParsingException
     */
    private function parseRevocation(string $line): ?DateTime
    {
        if (preg_match('/Revocation Date:\s+(.+)/', $line, $matches)) {
            $dateString = $matches[1];
            $revocationDate = DateTime::createFromFormat('M d H:i:s Y T', $dateString, new DateTimeZone('UTC'));
            if ($revocationDate === false) {
                throw new CrlParsingException("Failed to parse date: $dateString");
            }
            return $revocationDate;
        }
        return null;
    }
}
