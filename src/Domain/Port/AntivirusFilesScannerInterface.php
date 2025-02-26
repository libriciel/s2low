<?php

namespace S2low\Domain\Port;

use S2low\Domain\Exception\VirusDetectedException;

interface AntivirusFilesScannerInterface
{
    /**
     * @param string $filePath
     * @return bool
     * @throws VirusDetectedException
 */
    public function scan(string $filePath): bool;
}