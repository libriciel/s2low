<?php

namespace S2low\Domain\Port;

interface AntivirusFilesScannerInterface
{
    public function scan(string $filePath): bool;
}