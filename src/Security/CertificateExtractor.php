<?php

namespace S2low\Security;

use S2lowLegacy\Lib\X509Certificate;
use Symfony\Component\HttpFoundation\Request;

class CertificateExtractor
{
    public function __construct(
        private readonly X509Certificate $x509Certificate
    ) {
    }

    /**
     * @return array{ssl_client_verify: string, subject_dn: string, issuer_dn: string, certificate_hash: string, ssl_client_cert: string, certificate_rgs_2_etoiles: string}|null
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
            'certificate_rgs_2_etoiles' => $this->extractRgs2Certificate($request),
        ];
    }

    public function hasValidCertificate(Request $request): bool
    {
        $cert = $request->server->get('SSL_CLIENT_CERT');
        $verify = $request->server->get('SSL_CLIENT_VERIFY');

        if (empty($cert)) {
            return false;
        }

        if ($verify !== 'SUCCESS') {
            return false;
        }

        return true;
    }

    private function extractRgs2Certificate(Request $request): string
    {
        $rgs2Header = $request->headers->get('org-s2low-forward-x509-identification');

        if (!$rgs2Header) {
            return '';
        }

        return X509Certificate::der2pem(base64_decode($rgs2Header));
    }
}
