<?php

class PadesValid
{
    private $pades_valid_url;


    /** @var  VerifyPadesSignature */
    private $verifyPadesSignature;

    private $last_result;

    /** @var CurlWrapperFactory */
    private $curlWrapperFactory;

    /** @var OpenSslWrapper  */
    private $openSslWrapper;


    public function __construct($pades_valid_url, $rgs_validca_path, OpenSslWrapper $openSslWrapper) {
        $this->pades_valid_url = $pades_valid_url;
        $this->rgs_validca_path = $rgs_validca_path;
        $this->setCurlWrapperFactory(new CurlWrapperFactory());
        $this->setVerifyPKCS7Signature(new VerifyPKCS7Signature($this->rgs_validca_path,new OpenSslWrapper()));
        $this->openSslWrapper = $openSslWrapper;
    }

    public function setCurlWrapperFactory(CurlWrapperFactory $curlWrapperFactory){
        $this->curlWrapperFactory = $curlWrapperFactory;
    }

    public function getLastResult()
    {
        return $this->last_result;
    }

    /**
     * @param $filepath
     * @param bool $certificateChecking
     * @return bool
     * @throws RecoverableException
     * @throws \Exception
     */
    public function validate(string $filepath, bool $certificateChecking = true): bool
    {
        $result = $this->getPadesValidResult($filepath);
        if ($result === false) {
            return false;
        }
        foreach ($result->signatures as $signature) {
            if ($certificateChecking) {
                $this->verifyPadesSignature->validateSignature($signature);
            } else {
                $this->verifyPadesSignature->validateSignatureWithoutCertificateChecking($signature);
            }
        }
        return true;
    }

    /**
     * @param $filepath
     * @return bool|mixed
     * @throws Exception
     * @throws RecoverableException
     */
    private function getPadesValidResult($filepath)
    {
        $curlWrapper = $this->curlWrapperFactory->getNewInstance();

        $curlWrapper->addPostFile('file', $filepath);
        $result = $curlWrapper->get($this->pades_valid_url);
        $this->last_result = $result;
        if (!$result) {
            if ($curlWrapper->getLastHttpCode()) {
                throw new Exception($curlWrapper->getLastError() . " " . $curlWrapper->getLastOutput());
            }

            throw new RecoverableException($curlWrapper->getLastError() . " " . $curlWrapper->getLastOutput());
        }
        $result = json_decode($result);
        if (!$result) {
            throw new Exception("Impossible de décoder le message de pades-valid : " . $curlWrapper->getLastOutput());
        }
        if (!isset($result->signed)) {
            throw new Exception("Impossible de determiner si le fichier est signé");
        }

        if ($result->signed == false) {
            //Le fichier n'est pas signée
            return false;
        }
        if (empty($result->signatures)) {
            throw new Exception("Impossible de determiner si le fichier est signé");
        }
        return $result;
    }

    private function validSignature($signature){
    	$this->validSignatureWithoutCertificateChecking($signature);
    	$certificateObject = new CertificateFromPKCS7($this->rgs_validca_path,$this->openSslWrapper);   //TODO : create Temp file Factory
    	$certificateObject->setCertificateContent($signature->pemCertificate);
        try {
            $certificateObject->check();
        } catch (Exception $e){
            unlink($certificateObject->getPathOnDrive());
            throw $e;
        }
        unlink($certificateObject->getPathOnDrive());
        return true;
    }
}
