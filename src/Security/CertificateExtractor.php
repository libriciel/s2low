<?php

namespace S2low\Security;

use Exception;
use S2low\Security\Exceptions\CertificateExtractionException;
use S2lowLegacy\Lib\X509Certificate;
use Symfony\Component\HttpFoundation\Request;

class CertificateExtractor
{
    public function __construct(
        private readonly X509Certificate $x509Certificate
    ) {
    }

    /**
     * @return array{ssl_client_verify: string, subject_dn: string, issuer_dn: string, certificate_hash: string, ssl_client_cert: string}|null
     * @throws Exception
     */
    public function extract(Request $request): ?array
    {
        if (!$this->hasValidCertificate($request)) {
            return null;
        }

        $certificateContent = $request->server->get('SSL_CLIENT_CERT');
        $certificateInfo = $this->x509Certificate->getInfo($certificateContent);

        if (!$certificateInfo) {
            return null;
        }

        return [
            'ssl_client_verify' => $request->server->get('SSL_CLIENT_VERIFY'),
            'subject_dn' => $certificateInfo['subject_name'],
            'issuer_dn' => $certificateInfo['issuer_name'],
            'certificate_hash' => $certificateInfo['certificate_hash'],
            'ssl_client_cert' => $certificateContent,
        ];
    }

    /**
     * @return array{ssl_client_verify: string, subject_dn: string, issuer_dn: string, certificate_hash: string, ssl_client_cert: string}
     * @throws CertificateExtractionException
     */
    public function extractCertificateOrFail(Request $request): array
    {
        try {
            $certificateInfo = $this->extract($request);
        } catch (Exception $exception) {
            throw new CertificateExtractionException('Une erreur est survenu pendant la lecture du certificat : ' . $exception->getMessage());
        }

        if (!$certificateInfo) {
            throw new CertificateExtractionException('Aucun certificat trouvé.');
        }

        return $certificateInfo;
    }

    public function hasValidCertificate(Request $request): bool
    {
        return $this->isCertificateVerified($request) && $this->hasCertificateContent($request);
    }

    private function isCertificateVerified(Request $request): bool
    {
        return $request->server->get('SSL_CLIENT_VERIFY') === 'SUCCESS';
    }

    private function hasCertificateContent(Request $request): bool
    {
        return $request->server->get('SSL_CLIENT_CERT') !== null;
    }
}
