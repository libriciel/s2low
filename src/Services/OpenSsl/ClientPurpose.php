<?php

namespace S2low\Services\OpenSsl;

class ClientPurpose
{
    public function check(string $certificate, string $certificateStorePath): bool
    {
        return openssl_x509_checkpurpose($certificate, X509_PURPOSE_SSL_CLIENT, [$certificateStorePath]) === true;
    }
}
