<?php

namespace S2low\Domain\Contract;

interface AntivirusFilesScannerInterface
{
    public function scan(string $filePath): bool;
}