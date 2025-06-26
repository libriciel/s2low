<?php

namespace S2lowLegacy\Lib;

use Exception;

class CertificateBundle
{
    /** @var \S2lowLegacy\Lib\PemCertificate[] */
    private array $certificates;

    public function __construct(array $certificates)
    {
        $this->certificates = $certificates;
    }

    /**
     * @throws Exception
     */
    public function getCertificateWithSubjectMatching(array $expectedSubject): null|PemCertificate
    {
        foreach ($this->certificates as $certificate) {
            if ($expectedSubject === $certificate->getSubjectDN()) {
                return $certificate;
            }
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function getCertificateWithIssuerMatching(array $expectedIssuer): null|PemCertificate
    {
        foreach ($this->certificates as $certificate) {
            if ($expectedIssuer === $certificate->getIssuerDN() && !$certificate->isAutosigned()) {
                return $certificate;
            }
        }
        return null;
    }
}
