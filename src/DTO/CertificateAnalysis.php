<?php

namespace S2low\DTO;

class CertificateAnalysis
{
    public function __construct(
        public readonly bool $isRgs,
        public readonly ?string $message = null
    ) {
    }
}
