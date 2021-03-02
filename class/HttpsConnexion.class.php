<?php


class HttpsConnexion
{
    /** @var Environnement */
    private $environnement;
    /** @var X509Certificate  */
    private $certificateHandler;

    public function __construct(Environnement $environnement, X509Certificate $certificateHandler){
        $this->environnement = $environnement;
        $this->certificateHandler=$certificateHandler;
    }

    private function der2pem($der_data): string
    {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
        return $pem;
    }

    private function getParameterList($correspondanceArray,$localisation): array
    {
        $result = array();
        foreach ($correspondanceArray as $server_key => $result_key){
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

        $result = $this->getParameterList([
                'SSL_CLIENT_VERIFY' => 'ssl_client_verify',
                'SSL_CLIENT_S_DN' => 'subject_dn',
                'SSL_CLIENT_I_DN' => 'issuer_dn',
                'SSL_CLIENT_CERT' => 'ssl_client_cert',
                'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION' => 'certificate_rgs_2_etoiles',
                'TESTING_CERTIFICATE_HASH' => 'certificate_hash'],
            "server");

        if (! $result['ssl_client_verify']){
            return false;
        }

        if ($result['ssl_client_cert']){
            $info = $this->certificateHandler->getInfo($result['ssl_client_cert']);
            if (! $info){
                return false;
            }
            $result['issuer_dn'] = $info['issuer_name'];
            $result['subject_dn'] = $info['subject_name'];
            $result['certificate_hash'] = $info['certificate_hash'];
        }
        if ($result['certificate_rgs_2_etoiles']){
            $result['certificate_rgs_2_etoiles'] = $this->der2pem(base64_decode($result['certificate_rgs_2_etoiles']));
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getCredentialsFromApache(): array
    {
        return $this->getParameterList([
                'PHP_AUTH_USER' => 'login',
                'PHP_AUTH_PW' => 'password'],"server");
    }

    /**
     * @return array
     */
    public function getCredentialsFromPost(): array
    {
        $parameterList = $this->getParameterList([
            'login' => 'login',
            'password' => 'password'], "post");
        return $parameterList;
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

    public function getCertificateHash() : string
    {
        return $this->environnement->server()->get('TESTING_CERTIFICATE_HASH');
    }

    public function hasNonceParameters(): bool
    {
        return !empty($this->environnement->get()->get('nounce'));
    }
}