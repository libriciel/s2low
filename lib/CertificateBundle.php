<?php

namespace S2lowLegacy\Lib;

class CertificateBundle
{
    /** @var \S2lowLegacy\Lib\PemCertificate[] */
    private array $certificates;

    public function __construct(PemCertificate ...$certificates)
    {
        $this->certificates = $certificates;
    }

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

    /**
     * @throws \PHPUnit\lib\CertificateChainException
     */
    public function getCertificateChain(PemCertificate $certificate): CertificateChain
    {
        $chain = new CertificateChain($certificate);
        while (
            !$chain->hasValidPathToRoot() && (
            $nextCertificateInChain = $this->findBySubjectDN(
                $chain->getLastIssuerDN()
            )) !== null
        ) {
            $chain->appendCertificate($nextCertificateInChain);
        }
        return $chain;
    }

    /**
     * @return \S2lowLegacy\Lib\PemCertificate[]
     */
    public function findCertificatesWithNoChildren(): array
    {
        $result = [];
        foreach ($this->certificates as $certificate) {
            if ($this->hasNoChildren($certificate)) {
                $result[] = $certificate;
            }
        }
        return $result;
    }

    /**
     * @param \S2lowLegacy\Lib\PemCertificate $certificate
     * @return bool
     */
    public function hasNoChildren(PemCertificate $certificate): bool
    {
        return $this->findByIssuerDN($certificate->getSubjectDN()) === null;
    }
}
