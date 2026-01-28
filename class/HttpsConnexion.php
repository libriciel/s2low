<?php

namespace S2lowLegacy\Class;

use Exception;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\X509Certificate;

class HttpsConnexion
{
    /** @var Environnement */
    private $environnement;
    /** @var X509Certificate  */
    private $certificateHandler;

    public function __construct(
        Environnement $environnement,
        X509Certificate $certificateHandler
    ) {
        $this->environnement = $environnement;
        $this->certificateHandler = $certificateHandler;
    }

    private function der2pem(string $der_data): string
    {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }

    private function getParameterList(array $correspondanceArray, string $localisation): array
    {
        $result = array();
        foreach ($correspondanceArray as $server_key => $result_key) {
            if (!$this->environnement->$localisation()->get($server_key)) {
                $result[$result_key] = false;
            } else {
                $result[$result_key] = $this->environnement->$localisation()->get($server_key);
            }
        }
        return $result;
    }

    /**
     * @return array|false
     * @throws Exception
     */
    public function getCertificateInfo()
    {
        $result = $this->getParameterList(
            [
                'SSL_CLIENT_VERIFY' => 'ssl_client_verify',
                'SSL_CLIENT_S_DN' => 'subject_dn',
                'SSL_CLIENT_I_DN' => 'issuer_dn',
                'SSL_CLIENT_CERT' => 'ssl_client_cert',
                'TESTING_CERTIFICATE_HASH' => 'certificate_hash'],
            "server"
        );

        if (! $result['ssl_client_verify']) {
            return false;
        }

        if ($result['ssl_client_cert']) {
            $info = $this->certificateHandler->getInfo($result['ssl_client_cert']);
            if (! $info) {
                return false;
            }
            $result['issuer_dn'] = $info['issuer_name'];
            $result['subject_dn'] = $info['subject_name'];
            $result['certificate_hash'] = $info['certificate_hash'];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getCredentialsFromApache(): array
    {
        return [
            'login' => $this->environnement->server()->get('PHP_AUTH_USER'),
            'password' => $this->environnement->server()->get('PHP_AUTH_PW')
        ];
    }

    /**
     * @return array
     */
    public function getCredentialsFromPost(): array
    {
        return $this->getParameterList([
            'login' => 'login',
            'password' => 'password'], "post");
    }

    /**
     * @return array
     */
    public function getNonceParameters(): array
    {
        return [$this->environnement->get()->get('login'),
            $this->environnement->get()->get('nounce'),
            $this->environnement->get()->get('hash')];
    }

    public function getCertificateHash(): string
    {
        return $this->getCertificateInfo()['certificate_hash'];
    }

    public function hasNonceParameters(): bool
    {
        return !empty($this->environnement->get()->get('nounce'));
    }
}
