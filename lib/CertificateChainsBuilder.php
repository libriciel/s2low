<?php

namespace S2lowLegacy\Lib;

use Exception;

class CertificateChainsBuilder
{
    /**
     * @throws Exception
     */
    public function build(array $certificates): array
    {
        $certificateBundle = new CertificateBundle($certificates);
        // INIT :
        // Créer indexed array des certificats par Subject
        // Récupérer tous les subjects qui ne sont pas présents dans les issuers
        //  - Pour chacun de ces subjects, créer une CertificateChain
        //  - créer un array IncompleteCertificateChains contenant ces certificats
        // de CertificateChains
        // STEP :
        // Pour chaque CertificateChain :
        //    -> vérifier que le dernier issuer est dans l'array des Subjects
        //          -> Si oui, on ajoute le certificat
        //              -> Si la CertificateChain fini par un autosigné, on la met dans un array CompleteCertificateChains
        //          -> Si non, on ajoute la CertificateChain dans un array CompleteCertificateChains
        // On arrête quand l'array IncompleteCertificateChains est vide
        //
        /** @var CertificateChain[] $certificateChainsToComplete */
        $certificateChainsToComplete = [];
        $completeCertificateChains = [];
        foreach ($certificates as $certificate) {
            if (is_null($certificateBundle->getCertificateWithIssuerMatching($certificate->getSubjectDN()))) {
                $certificateChainsToComplete[] = new CertificateChain([$certificate]);
            }
        }
        while (!empty($certificateChainsToComplete)) {
            foreach ($certificateChainsToComplete as $key => $certificateChain) {
                if ($certificateChain->hasValidPathToRoot()) {
                    $completeCertificateChains[] = $certificateChain;
                    unset($certificateChainsToComplete[$key]);
                    continue;
                }
                $nextCertificateInPath = $certificateBundle->getCertificateWithSubjectMatching(
                    $certificateChain->getLastIssuerDN()
                );

                if (is_null($nextCertificateInPath)) {
                    $completeCertificateChains[] = $certificateChain;
                    unset($certificateChainsToComplete[$key]);
                    continue;
                }

                $certificateChain->addCertificateInPath(
                    $nextCertificateInPath
                );
            }
        }
        return $completeCertificateChains;
    }
}
