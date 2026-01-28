<?php

namespace S2low\DTO;

class XadesSignatureValidationResult
{
    public function __construct(
        public readonly bool $verification_success,
        public readonly ?string $xades_output,
        public readonly ?string $errorMessage = null
    ) {
    }
}
