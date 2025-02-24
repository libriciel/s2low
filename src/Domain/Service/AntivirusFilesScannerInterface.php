<?php

namespace S2low\Domain\Service;

interface AntivirusFilesScannerInterface
{
    public function scan(string $filePath): bool;
}