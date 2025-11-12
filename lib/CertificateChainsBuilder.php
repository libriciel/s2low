<?php

namespace S2lowLegacy\Lib;

class CertificateChainsBuilder
{
    /**
     * @return CertificateChain[]
     * @throws \PHPUnit\lib\CertificateChainException
     */
    public function build(PemCertificate ...$certificates): array
    {
        $certificateBundle = new CertificateBundle(...$certificates);

        $certificates = $certificateBundle->findCertificatesWithNoChildren();
        $chains = [];

        foreach ($certificates as $chain) {
            $chains[] = $certificateBundle->getCertificateChain($chain);
        }
        return $chains;
    }
}
