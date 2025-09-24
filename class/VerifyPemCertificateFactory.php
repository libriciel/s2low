<?php

namespace S2lowLegacy\Class;

use S2low\Services\Certificates\CRLReader;
use S2low\Services\ProcessCommand\CheckSnInCRLCommandOutputTranslator;
use S2low\Services\ProcessCommand\CheckSnInCRLFactory;
use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;

class VerifyPemCertificateFactory
{
    public function get(string $caCertificatesPath): VerifyPemCertificate
    {
        return new VerifyPemCertificate(
            $caCertificatesPath,
            new OpenSSLWrapper($caCertificatesPath, new CommandLauncher(), new CheckSnInCRLFactory(new CRLReader()))
        );
    }
}
