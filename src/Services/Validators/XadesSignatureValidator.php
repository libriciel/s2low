<?php

namespace S2low\Services\Validators;

use S2low\DTO\XadesSignatureValidationResult;
use S2low\Infrastructure\CertificateStores\Stores;
use S2low\Infrastructure\CertificateStores\Type;
use S2lowLegacy\Lib\XadesSignature;

class XadesSignatureValidator
{
    public function __construct(
        private readonly Stores $stores,
        private readonly XadesSignature $xadesSignature,
    ) {
    }

    /**
     * @throws \S2low\Exceptions\UndefinedCertificateStore
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function validate(string $filepath, Type $type = Type::DEFAULT): XadesSignatureValidationResult
    {
        return $this->xadesSignature->verifyWithReturn(
            $filepath,
            $this->stores->getStorePath($type)
        );
    }
}
