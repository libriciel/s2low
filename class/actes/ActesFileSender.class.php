<?php

class ActesFileSender {

    private $actesMinistereProperties;

    public function __construct(
        ActesMinistereProperties $actesMinistereProperties
    ) {
        $this->actesMinistereProperties = $actesMinistereProperties;
    }

    public function send($filepath){
       $curlWrapper = new CurlWrapper();

        $url = $this->actesMinistereProperties->url;

        if (substr($url,0,5)=='https'){
            $curlWrapper->setProperties( CURLOPT_SSL_VERIFYHOST , 0 );
            $curlWrapper->setProperties(  CURLOPT_CERTINFO, 1);
        }

        if(  $this->actesMinistereProperties->authentification_type == ActesMinistereProperties::AUTHENTICATION_POST){
            $url .= "?user={$this->actesMinistereProperties->login}&password={$this->actesMinistereProperties->password}";
        }
        if ($this->actesMinistereProperties->authentification_type == ActesMinistereProperties::AUTHENTICATION_BASIC){
            $curlWrapper->httpAuthentication($this->actesMinistereProperties->login,$this->actesMinistereProperties->password);
        }

        $curlWrapper->setClientCertificate(
            $this->actesMinistereProperties->client_certificate,
            $this->actesMinistereProperties->client_certificate_key,
            $this->actesMinistereProperties->client_certificate_key_password
        );

        $curlWrapper->addPostFile(basename($filepath),$filepath);

        $curlWrapper->get($url);

        if ($curlWrapper->getHTTPCode() != 200){
            throw new Exception($curlWrapper->getLastError());
        }

        if (substr($url,0,5)=='https'){
            $x509Certificate = new X509Certificate();

            $actual_certificat = $curlWrapper->getServerCertificate();
            $expected_certificat = file_get_contents($this->actesMinistereProperties->server_certificate_path);

            $actual_hash = $x509Certificate->getBase64Hash($actual_certificat);
            $expected_hash = $x509Certificate->getBase64Hash($expected_certificat);

            if ($actual_hash != $expected_hash){
                throw new Exception("Le certificat recu ($actual_hash) ne correspond pas à celui attendu ($expected_hash)");
            }
        }

        return true;
    }

}