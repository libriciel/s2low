<?php

require_once("OpensslTSWrapper.class.php");
require_once("SoapClientFactory.class.php");

class OpenSign
{
    private const DEFAULT_TIMEOUT = 2;

    private $wsdl;
    private $soapClientFactory;
    private $opensign_ca;
    private $opensign_x509;

    private $opensslTSWrapper;

    public function __construct(OpensslTSWrapper $opensslTSWrapper, SoapClientFactory $soapClientFactory)
    {
        $this->opensslTSWrapper = $opensslTSWrapper;
        $this->soapClientFactory = $soapClientFactory;
    }

    public function setConfig($wsdl, $opensign_ca_path, $opensign_x509_path, $opensign_timeout = 0)
    {
        $this->wsdl = $wsdl;
        $this->opensign_ca = $opensign_ca_path;
        $this->opensign_x509 = $opensign_x509_path;
        $this->opensign_timeout = $opensign_timeout ?: self::DEFAULT_TIMEOUT;
    }

    public function getTimestampReply($data)
    {
        try {
            $timestampRequest = $this->opensslTSWrapper->getTimestampQuery($data);
            $token = $this->getToken($timestampRequest);
            return $token;
        } catch (exception $e) {
            return false;
        }
    }

    public function test()
    {
        $soapClient = $this->getSoapClient();
        return $soapClient->wsEcho("Hello World !");
    }

    private function getSoapClient()
    {
        $soapClient = $this->soapClientFactory->getInstance($this->wsdl, array('connection_timeout' => $this->opensign_timeout));
        return $soapClient;
    }

    private function getToken($timestampRequest)
    {
        $soapClient = $this->getSoapClient();
        $response = $soapClient->createResponse(array('request' => base64_encode($timestampRequest)));
        if (!$response) {
            throw new OpenSignException("Impossible de récuperer le token");
        }
        return base64_decode($response);
    }

    public function verify($data, $token)
    {
        $config_file = __DIR__ . "/data/openssl-tsa.cnf";
        $result = $this->opensslTSWrapper->verify($data, $token, $this->opensign_ca, $this->opensign_x509, $config_file);
        if (! $result) {
            throw new Exception($this->opensslTSWrapper->getLastError());
        }
        return $result;
    }
}
