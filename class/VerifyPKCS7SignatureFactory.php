<?php

namespace S2lowLegacy\Class;

use S2low\Services\Certificates\CRLReader;
use S2low\Services\ProcessCommand\CheckSnInCRLFactory;
use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Lib\PemCertificateFactory;

class VerifyPKCS7SignatureFactory
{
    public static function create(string $authorized_ca_path = RGS_VALIDCA_PATH): VerifyPKCS7Signature
    {
        return new VerifyPKCS7Signature(
            $authorized_ca_path,
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory(),
            new OpenSSLWrapper(
                $authorized_ca_path,
                new CommandLauncher(),
                new CheckSnInCRLFactory(new CRLReader())
            )
        );
    }
}
