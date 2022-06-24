<?php

class TestTedetisApi
{
    private $beginUrl;

    private $certificateFile;
    private $certificateKey;
    private $certificateKeyPass;

    private $lastData;
    private $lastError;
    private $lastInfo;

    public function __construct($beginUrl)
    {
        $this->beginUrl = $beginUrl;
    }

    public function setCertificate($certificateFile, $certificateKey, $certificateKeyPass)
    {
        $this->certificateFile = $certificateFile;
        $this->certificateKey = $certificateKey;
        $this->certificateKeyPass = $certificateKeyPass;

        print_r($this);
    }

    public function get($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->beginUrl . $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificateFile);
        curl_setopt($ch, CURLOPT_SSLKEY, $this->certificateKey);
        curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->certificateKeyPass);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('api' => 1));

        $this->lastData = curl_exec($ch) . "\n";

        if (! $this->lastData) {
            $this->lastError = curl_error($ch);
        }

        $this->lastInfo = curl_getinfo($ch);
        curl_close($ch);

        return $this->lastData;
    }
}
