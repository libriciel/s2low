<?php

namespace S2lowLegacy\Lib;

use Exception;
use PHPUnit\lib\CertificateChainException;

class CertificateChain
{
    /**
     * @var \S2lowLegacy\Lib\PemCertificate[]
     */
    private array $certificates;

    public function __construct(PemCertificate ...$certificates)
    {
        $this->certificates = $certificates;
    }

    private function isChainSequentiallyValid(): bool
    {
        $nbOfCertificatesInChain = count($this->certificates);
        for ($i = 0; $i < $nbOfCertificatesInChain - 1; $i++) {
            $currentCertificate = $this->certificates[$i];
            $nextCertificateInChain = $this->certificates[$i + 1];
            if (!$nextCertificateInChain->isIssuedBy($currentCertificate)) {
                return false;
            }
        }
        return true;
    }

    public function hasValidPathToRoot(): bool
    {
        return $this->isChainSequentiallyValid() && $this->getLastCertificate()->isAutosigned();
    }

    /**
     * @throws \PHPUnit\lib\CertificateChainException
     */
    public function appendCertificate(PemCertificate $pemCertificate): void
    {
        if (!$pemCertificate->isIssuedBy($this->getLastCertificate())) {
            throw new CertificateChainException(
                sprintf(
                    'Erreur lors de la création de la chaine de certification (%s ne correspond pas à l\'emetteur %s)',
                    implode('/', $pemCertificate->getIssuerDN()),
                    implode('/', $this->getLastCertificate()->getSubjectDN())
                )
            );
        }
        $this->certificates[] = $pemCertificate;
    }

    public function getLastIssuerDN(): array
    {
        return $this->getLastCertificate()->getIssuerDN();
    }

    public function getLeafSubjectDN(): array
    {
        return $this->certificates[0]->getSubjectDN();
    }

    public function getCertificates(): array
    {
        return $this->certificates;
    }

    /**
     * @throws CertificateChainException
     */
    public function checkValidity(): void
    {
        foreach ($this->certificates as $certificate) {
            try {
                $certificate->checkValidity();
            } catch (Exception $e) {
                throw new CertificateChainException(
                    '[' . $certificate->getSubjectDN()['CN'] . '] : ' . $e->getMessage()
                );
            }
        }
        if (!$this->hasValidPathToRoot()) {
            throw new CertificateChainException('Chaine sans certificat racine');
        }
    }

    private function getLastCertificate(): PemCertificate
    {
        return $this->certificates[count($this->certificates) - 1];
    }
}
