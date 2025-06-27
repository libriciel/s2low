<?php

namespace S2lowLegacy\Lib;

use Exception;

class CertificateChainsBuilder
{
    /**
     * @return CertificateChain[]
     * @throws \PHPUnit\lib\CertificateChainException
     */
    public function build(PemCertificate ...$certificates): array
    {
        $certificateBundle = new CertificateBundle(...$certificates);

        /** @var CertificateChain[] $unfinishedChains */
        $completeCertificateChains = [];
        $unfinishedChains = $this->initChains($certificateBundle, ...$certificates);

        while (!empty($unfinishedChains)) {
            foreach ($unfinishedChains as $key => $certificateChain) {
                if ($certificateChain->hasValidPathToRoot()) {
                    $completeCertificateChains[] = $certificateChain;
                    unset($unfinishedChains[$key]);
                    continue;
                }
                $nextCertificateInPath = $certificateBundle->findBySubjectDN(
                    $certificateChain->getLastIssuerDN()
                );

                if (is_null($nextCertificateInPath)) {
                    $completeCertificateChains[] = $certificateChain;
                    unset($unfinishedChains[$key]);
                    continue;
                }

                $certificateChain->appendCertificate(
                    $nextCertificateInPath
                );
            }
        }
        return $completeCertificateChains;
    }

    /**
     * @return CertificateChain[]
     */
    private function initChains(CertificateBundle $certificateBundle, PemCertificate ...$certificates): array
    {
        $certificateChainsToComplete = [];
        foreach ($certificates as $certificate) {
            if (is_null($certificateBundle->findByIssuerDN($certificate->getSubjectDN()))) {
                $certificateChainsToComplete[] = new CertificateChain($certificate);
            }
        }
        return $certificateChainsToComplete;
    }
}
