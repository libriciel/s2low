<?php

namespace S2low\Services\Validators;

use S2low\Infrastructure\CertificateStores\Stores;
use S2low\Infrastructure\CertificateStores\Type;
use S2lowLegacy\Class\VerifyPKCS7Signature;

class PKCS7SignatureValidator
{
    public function __construct(
        private readonly Stores $stores,
        private readonly VerifyPKCS7Signature $verifyPKCS7Signature
    ) {
    }

    /**
     * @throws \S2low\Exceptions\UndefinedCertificateStore
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function validate(
        $signature,
        ?string $file_path = null,
        Type $type = Type::DEFAULT,
        array $filteredErrors = []
    ): bool {
        return $this->verifyPKCS7Signature->verifySignature(
            $signature,
            $this->stores->getStorePath($type),
            $filteredErrors,
            $file_path
        );
    }
}
