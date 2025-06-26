<?php

namespace S2lowLegacy\Lib;

use Exception;

class CertificateChain
{
    /**
     * @var \S2lowLegacy\Lib\PemCertificate[]
     */
    private array $certificates;

    public function __construct(array $certificates)
    {
        $this->certificates = $certificates;
    }

    public function hasValidPathToRoot(): bool
    {
        $expectedSubjectDN = $this->certificates[0]->getSubjectDN();
        $lastCertificateKey = count($this->certificates) - 1;
        foreach ($this->certificates as $key => $certificate) {
            if ($certificate->getSubjectDN() !== $expectedSubjectDN) {
                return false;
            }
            if ($key === $lastCertificateKey) {
                break;
            }
            $expectedSubjectDN = $certificate->getIssuerDN();
        }
        return $certificate->isAutosigned();
    }

    public function addCertificateInPath(PemCertificate $pemCertificate): void
    {
        $this->certificates[] = $pemCertificate;
    }

    public function getLastIssuerDN(): array
    {
        return end($this->certificates)->getIssuerDN();
    }

    public function getLeafSubjectDN(): array
    {
        return $this->certificates[0]->getSubjectDN();
    }

    public function getCertificates(): array
    {
        return $this->certificates;
    }

    public function checkValidity()
    {
        foreach ($this->certificates as $certificate) {
            $certificate->checkValidity();
        }
        if (!$this->hasValidPathToRoot()) {
            throw new Exception('Chaine sans certificat racine');
        }
    }
}
