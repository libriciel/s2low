<?php

namespace S2lowLegacy\Class;

class ConnectionInfos
{
    /**
     * @var \S2lowLegacy\Class\Credentials
     */
    private Credentials $credentials;
    private array $certificate;

    public function __construct(Credentials $credentials, array $certificate)
    {
        $this->credentials = $credentials;
        $this->certificate = $certificate;
    }

    public function getLogin()
    {
        return $this->credentials->getLogin();
    }

    public function getPassword()
    {
        return $this->credentials->getPassword();
    }

    public function getCertificateHash()
    {
        return $this->certificate['certificate_hash'];
    }

    public function getCertificateRGS()
    {
        return $this->certificate['certificate_rgs_2_etoiles'];
    }

    public function getSslClientVerify()
    {
        return $this->certificate['ssl_client_verify'];
    }

    public function getSubjectDN()
    {
        return $this->certificate['subject_dn'];
    }

    public function getIssuerDN()
    {
        return $this->certificate['issuer_dn'];
    }

    public function getSslClientCert()
    {
        return $this->certificate['ssl_client_cert'];
    }
}
