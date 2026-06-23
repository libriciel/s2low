<?php

namespace PHPUnit\class;

use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\VerifyPKCS7Signature;
use S2lowLegacy\Lib\PemCertificateFactory;

class VerifyPKCS7SignatureFactory
{
    public function __construct(
        private readonly VerifyPemCertificate $verifyPemCertificate,
        private readonly PemCertificateFactory $pemCertificateFactory,
        private readonly OpenSSLWrapper $openSSLWrapper,
    ) {
    }
    public function create(string $authorized_ca_path): VerifyPKCS7Signature
    {
        return new VerifyPKCS7Signature(
            $authorized_ca_path,
            $this->verifyPemCertificate,
            $this->pemCertificateFactory,
            $this->openSSLWrapper
        );
    }
}
