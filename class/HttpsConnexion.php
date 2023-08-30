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
    private bool $convertAPILoginsFromIso;

    public function __construct(
        Environnement $environnement,
        X509Certificate $certificateHandler,
        $convert_api_logins_from_iso
    ) {
        $this->environnement = $environnement;
        $this->certificateHandler = $certificateHandler;
        $this->convertAPILoginsFromIso = $convert_api_logins_from_iso;
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
        //http://stackoverflow.com/a/18205049
        if (function_exists('apache_request_headers')) {    //TODO : tester quand on utilisera les namespace
            $h = apache_request_headers();
            if (isset($h['org.s2low.forward-x509-identification'])) {
                $this->environnement->server()->set('HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION', $h['org.s2low.forward-x509-identification']);
            }
        }

        $result = $this->getParameterList(
            [
                'SSL_CLIENT_VERIFY' => 'ssl_client_verify',
                'SSL_CLIENT_S_DN' => 'subject_dn',
                'SSL_CLIENT_I_DN' => 'issuer_dn',
                'SSL_CLIENT_CERT' => 'ssl_client_cert',
                'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION' => 'certificate_rgs_2_etoiles',
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
        if ($result['certificate_rgs_2_etoiles']) {
            $result['certificate_rgs_2_etoiles'] = $this->der2pem(base64_decode($result['certificate_rgs_2_etoiles']));
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getCredentialsFromApache(): array
    {
        // Il faudra supprimer la fonction correctEncoding lorsque l'on supprimera la constante convertAPILoginsToIso
        return [
            'login' => $this->correctEncoding($this->environnement->server()->get('PHP_AUTH_USER')),
            'password' => $this->correctEncoding($this->environnement->server()->get('PHP_AUTH_PW'))
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

    private function correctEncoding(mixed $get)
    {
        if ($this->convertAPILoginsFromIso) {
            return mb_convert_encoding($get, 'UTF-8', 'ISO-8859-1');
        }
        return $get;
    }
}
