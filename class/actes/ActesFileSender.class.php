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
            $certificat_cn = $curlWrapper->getServerCertificateCommonName();
            $expected_cn = $this->actesMinistereProperties->server_certificate_cn;

            if ($certificat_cn != $expected_cn){
                throw new Exception("Le certificat présenté n'a pas le bon CN : $certificat_cn ($expected_cn attendu)");
            }
        }

        return true;
    }

}