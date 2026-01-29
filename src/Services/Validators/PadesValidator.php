<?php

namespace S2low\Services\Validators;

use S2low\DTO\PadesValidationResult;
use S2low\Infrastructure\CertificateStores\Stores;
use S2low\Infrastructure\CertificateStores\Type;
use S2lowLegacy\Class\PadesValid;

class PadesValidator
{
    public function __construct(
        private readonly Stores $stores,
        private readonly PadesValid $padesValid,
    ) {
    }

    /**
     * @throws \S2low\Exceptions\UndefinedCertificateStore
     */
    public function validate(string $filepath, Type $type = Type::DEFAULT): PadesValidationResult
    {
        return $this->padesValid->validate(
            $filepath,
            $this->stores->getStorePath($type)
        );
    }
}
