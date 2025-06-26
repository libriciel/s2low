<?php

namespace S2lowLegacy\Lib;

class CertificateChainsBuilder
{
    public function build(array $certificates): array
    {
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
    }
}