<?php

namespace S2lowLegacy\Class;

use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;

class VerifyPemCertificateFactory
{
    public function get(string $caCertificatesPath): VerifyPemCertificate
    {
        return new VerifyPemCertificate(
            new OpenSSLWrapper(new CommandLauncher())
        );
    }
}
