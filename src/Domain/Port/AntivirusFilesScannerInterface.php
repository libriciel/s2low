<?php

namespace S2low\Domain\Port;

use S2low\Domain\Exception\VirusDetectedException;

interface AntivirusFilesScannerInterface
{
    /**
     * @param string $filePath
     * @throws VirusDetectedException
     */
    public function scan(string $filePath): void;
}
