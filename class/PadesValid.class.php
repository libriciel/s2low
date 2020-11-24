<?php

class PadesValid {

    private $pades_valid_url;
    private $rgs_validca_path;


    /** @var  VerifyPKCS7Signature */
    private $verifyPKCS7Signature;

    private $last_result;

    /** @var CurlWrapperFactory */
    private $curlWrapperFactory;

    public function __construct($pades_valid_url, $rgs_validca_path) {
        $this->pades_valid_url = $pades_valid_url;
        $this->rgs_validca_path = $rgs_validca_path;
        $this->setCurlWrapperFactory(new CurlWrapperFactory());
        $this->setVerifyPKCS7Signature(new VerifyPKCS7Signature($this->rgs_validca_path));
    }

    public function setCurlWrapperFactory(CurlWrapperFactory $curlWrapperFactory){
        $this->curlWrapperFactory = $curlWrapperFactory;
    }

    public function setVerifyPKCS7Signature(VerifyPKCS7Signature $verifyPKCS7Signature){
        $this->verifyPKCS7Signature = $verifyPKCS7Signature;
    }

    public function getLastResult(){
        return $this->last_result;
    }

	/**
	 * @param $filepath
	 * @return bool|mixed
	 * @throws Exception
	 * @throws RecoverableException
	 */
    public function validateWithoutCertificateChecking($filepath){
		$result = $this->getPadesValidResult($filepath);
		if ($result === false){
			return false;
		}
		foreach($result->signatures as $signature){
			$signature->pemCertificate = $this->getPERMCertificate($signature);
            $this->checkNecessaryFields($signature);
            $signature->x509_info = $this->parsePemCertificate($signature->pemCertificate);
            $this->checkCertificateWasValidAtSignatureTime($signature->x509_info, $signature);
		}
		return true;
	}


	/**
	 * @param $filepath
	 * @return bool
	 * @throws RecoverableException
	 * @throws Exception
	 */
    public function validate($filepath){
    	$result = $this->getPadesValidResult($filepath);
    	if ($result === false){
    		return false;
		}
        foreach($result->signatures as $signature){
         	$signature->pemCertificate = $this->getPERMCertificate($signature);
            $this->checkNecessaryFields($signature);
            $signature->x509_info = $this->parsePemCertificate($signature->pemCertificate);
            $this->checkCertificateWasValidAtSignatureTime($signature->x509_info, $signature);
            $this->validateCertificateFomSignature($signature->pemCertificate);
        }
        return true;
    }

	/**
	 * @param $filepath
	 * @return bool|mixed
	 * @throws Exception
	 * @throws RecoverableException
	 */
    private function getPadesValidResult($filepath){
		$curlWrapper = $this->curlWrapperFactory->getNewInstance();

		$curlWrapper->addPostFile('file',$filepath);
		$result = $curlWrapper->get($this->pades_valid_url);
		$this->last_result = $result;
		if (!$result){

			if ($curlWrapper->getLastHttpCode() ){
				throw new Exception($curlWrapper->getLastError()." ".$curlWrapper->getLastOutput());
			}

			throw new RecoverableException($curlWrapper->getLastError()." ".$curlWrapper->getLastOutput());
		}
		$result = json_decode($result);
		if (! $result){
			throw new Exception("Impossible de décoder le message de pades-valid : ".$curlWrapper->getLastOutput());
		}
		if (! isset($result->signed)){
			throw new Exception("Impossible de determiner si le fichier est signé");
		}

		if ($result->signed == false){
			//Le fichier n'est pas signée
			return false;
		}
		if (empty($result->signatures)){
			throw new Exception("Impossible de determiner si le fichier est signé");
		}
		return $result;
	}

    /**
     * @param $signature
     * @return bool
     * @throws Exception
     */
    private function validateCertificateFomSignature($certificateContent){
        $certificate_path = sys_get_temp_dir()."/s2low_valid_certifcate_".time().mt_rand(0,mt_getrandmax());
        file_put_contents($certificate_path,$certificateContent);
        try {
            $this->verifyPKCS7Signature->checkCertificateWithoutCheckingCertificateChain(
                $certificate_path,
                $this->getTimestampFromSignature($signature)
            );
        } catch (Exception $e){
            unlink($certificate_path);
            throw $e;
        }
        unlink($certificate_path);
        return true;
    }

    private function checkNecessaryFields($signature){
        if (empty($signature->valid) || ! $signature->valid){
            throw new Exception("Au moins une signature n'est pas valide");
        }
        if(empty($signature->signingCert)){
            throw new Exception("Impossible de récupérer le certificat de signature");
        };
        if (empty($signature->signatureDate)){
            throw new Exception("Impossible de determiner la date de la signature");
        }
    }

    private function parsePemCertificate($certificate){
        $x509_info = openssl_x509_parse($certificate);

        if(!$x509_info){
            throw new Exception("Problème à l'ouverture du certificat : ".openssl_error_string());
        }
        return $x509_info;
    }


    /**
     * @param $x509_info
     * @param $signature
     * @return void
     * @throws \Exception
     */
    private function checkCertificateWasValidAtSignatureTime($x509_info, $signature){
        $signatureDate = $this->getTimestampFromSignature($signature);

		if ($signatureDate < $x509_info['validFrom_time_t'] ||
			$signatureDate > $x509_info['validTo_time_t']
		) {
			throw new Exception("La date de la signature {$signature->signatureDate}" .
				" n'entre pas dans la date de validité du certitficat {$x509_info['validFrom_time_t']} - {$x509_info['validTo_time_t']}");
		}
	}

	private function getPERMCertificate($signature){
		$beginpem = "-----BEGIN CERTIFICATE-----\n";
		$endpem = "\n-----END CERTIFICATE-----\n";

		$signing_cert = implode("\n",str_split($signature->signingCert,78));
		return $beginpem.$signing_cert.$endpem;
	}

    /**
     * @param $signature
     * @return false|float
     */
    private function getTimestampFromSignature($signature)
    {
        return floor($signature->signatureDate / 1000);
    }


}