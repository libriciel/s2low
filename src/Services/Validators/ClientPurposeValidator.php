<?php

namespace S2low\Services\Validators;

use S2low\Infrastructure\CertificateStores\Stores;
use S2low\Infrastructure\CertificateStores\Type;
use S2low\Services\OpenSsl\ClientPurpose;

class ClientPurposeValidator
{
    public function __construct(
        private readonly Stores $stores,
        private readonly ClientPurpose $clientPurpose,
    ) {
    }
    public function validate(string $certificate, Type $type = Type::DEFAULT): bool
    {
        return $this->clientPurpose->check($certificate, $this->stores->getStorePath($type));
    }
}
