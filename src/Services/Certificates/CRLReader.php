<?php

namespace S2low\Services\Certificates;

use DateTime;
use DateTimeZone;
use RuntimeException;
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

        foreach ($lines as $line) {
            if (preg_match('/^\s*Serial Number:\s*([0-9A-F]+)/i', $line, $m)) {
                $currentSerialNumber = $m[1];
            } elseif ($currentSerialNumber !== null && preg_match('/Revocation Date:\s+(.+)/', $line, $m)) {
                $dateString = $m[1];
                $dt = DateTime::createFromFormat('M d H:i:s Y T', $dateString, new DateTimeZone('UTC'));
                if ($dt === false) {
                    throw new CrlParsingException("Failed to parse date: $dateString");
                }
                $revocations[] = new Revocation($currentSerialNumber, $dt);
                $currentSerialNumber = null;
            }
        }
        return new CRL(...$revocations);
    }
}
