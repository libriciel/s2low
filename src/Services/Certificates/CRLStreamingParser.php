<?php

namespace S2low\Services\Certificates;

use DateTime;
use DateTimeZone;
use S2low\Component\Process\StreamingParserInterface;
use S2low\Exceptions\CrlParsingException;

class CRLStreamingParser implements StreamingParserInterface
{
    private ?string $currentSerialNumber = null;
    private ?bool $revokes = null;
    public function __construct(
        private readonly string $checkedSerialNumber,
        private readonly DateTime $checkedDate
    ) {
    }
    /**
     * @throws \S2low\Exceptions\CrlParsingException
     */
    public function parseLine(string $line): void
    {
            $serial = $this->parseSerialNumber($line);
        if ($serial !== null) {
            $this->currentSerialNumber = $serial;
            return;
        }
        if ($this->currentSerialNumber !== $this->checkedSerialNumber) {
            return;
        }
            $revocationDate = $this->parseRevocation($line);
        if ($revocationDate !== null) {
                $this->revokes = $revocationDate < $this->checkedDate;
        }
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
    public function isFinished(): bool
    {
        return !is_null($this->revokes);
    }

    public function getResult(): bool
    {
        if (is_null($this->revokes)) {
            return false;
        }
        return $this->revokes;
    }
}
