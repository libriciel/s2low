<?php

namespace S2lowLegacy\Lib;

use Exception;

class CertificateBundle
{
    /** @var \S2lowLegacy\Lib\PemCertificate[] */
    private array $certificates;

    public function __construct(PemCertificate ...$certificates)
    {
        $this->certificates = $certificates;
    }

    /**
     * @throws Exception
     */
    public function findBySubjectDN(array $subjectDN): ?PemCertificate
    {
        foreach ($this->certificates as $certificate) {
            if ($subjectDN === $certificate->getSubjectDN()) {
                return $certificate;
            }
        }
        return null;
    }

    public function findByIssuerDN(array $issuerDN): ?PemCertificate
    {
        foreach ($this->certificates as $certificate) {
            if ($issuerDN === $certificate->getIssuerDN() && !$certificate->isAutosigned()) {
                return $certificate;
            }
        }
        return null;
    }
}
