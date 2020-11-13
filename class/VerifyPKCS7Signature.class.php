<?php

class VerifyPKCS7Signature {

	private $authorized_ca_path;
	/** @var OpenSslWrapper  */
    private $openSslWrapper;

    public function __construct($authorized_ca_path,OpenSslWrapper $openSslWrapper){
		$this->authorized_ca_path = $authorized_ca_path;
		$this->openSslWrapper = $openSslWrapper;
	}

	public function verify($file_path,$signature){
		try {
			$signatureObject = new SignatureFromPKCS7();
			$certificateObject = new CertificateFromPKCS7();

			$this->verifyThrow($file_path,$signature,$signatureObject,$certificateObject);
				
		} catch(Exception $e){
			throw $e;   #D'origine... Pourquoi le cleanup ne se fait pas ???
            $this->cleanUpTempFiles($certificateObject, $signatureObject);
            throw $e;
		}

        $this->cleanUpTempFiles($certificateObject, $signatureObject);
		return true;
	}

	private function verifyThrow($file_path, $signature, $signatureObject, $certificateObject){
        $signatureObject->setSignatureContent($signature);
        $certificateObject->setCertificateContent($signatureObject->getCertificate());
        $certificateObject->check();
        $this->checkFileAndSignature($signatureObject, $file_path);
    }

	public function verifyCertificate($signature_content){
        $signatureObject = new SignatureFromPKCS7();
        $certificateObject = new CertificateFromPKCS7();

        $signatureObject->setSignatureContent($signature_content);
        $certificateObject->setCertificateContent($signatureObject->getCertificate());
		try {
			$certificateObject->check();
			#Heu... Il n'y a *rien* si �a foire ??
            #V�rifier si le finally est lanc�
		} finally {
			$this->cleanUpTempFiles($certificateObject, $signatureObject);
		}
	}

	public function checkCertificate($certificate_path) {
        list($verifyCmd, $out, $ret, $result) = $this->openSslWrapper->verifyCertificate(
            $certificate_path,
            $this->authorized_ca_path
        );

        /*if ($ret != 0) {            # ??? Le retour peut-il être = 0 quand il y a une erreur ????
			throw new Exception("Erreur #$ret lors de la verification du certificat (commande : $verifyCmd) (result: $result)");
		}*/

        $nonBlockingVerifyErrors = [2,  # unable to get issuer certificate
                                    3,  # unable to get certificate CRL
                                    18, # self signed certificate
                                    19, # self signed certificate in certificate chain
                                    20, # unable to get local issuer certificate
                                    21, # unable to verify the first certificate
                                    ];

        $nonBlockingErrorThrown = false;
        foreach ($out as $line) {
		    if(preg_match("/error ([0123456789]+) at ([0123456789])+ depth lookup:(.*)/",$line,$matches)){
                var_dump($matches);
		        if(!in_array($matches[1],$nonBlockingVerifyErrors)){
                    throw new Exception("Erreur #{$matches[1]} lors de la verification du certificat (commande : $verifyCmd) (result: $result)");
                } else {
                    $nonBlockingErrorThrown = true;
                }
            }
			/*if (stripos($line, 'certificate revoked') !== false) {      # Pourquoi pourrait-on arriver ici ?
				throw new Exception("Erreur #$ret lors de la verification du certificat (commande : $verifyCmd) (result: $result)");
			}*/
		}
            //TODO V�rifier que ce n'est utilis� nulle part...'
	}

    /**
     * @param $certificate_file
     * @param $signature_file
     */
    private function cleanUpTempFiles($certificate_file, $signature_file): void
    {
        #TODO : Ne fonctionne pas correctement pour l'instant
        if (file_exists($certificate_file)) {
            unlink($certificate_file);
        }
        if (file_exists($signature_file)) {
            unlink($signature_file);
        }
    }

    /**
     * @param $signatureObject
     * @param $file_path
     * @throws Exception
     */
    private function checkFileAndSignature($signatureObject, $file_path)
    {
        $command = "openssl smime -in {$signatureObject->getPathOnDisk()} -inform PEM -verify -content $file_path -CApath {$this->authorized_ca_path} > /dev/null 2>&1";
        exec($command, $output, $return);
        $output = implode("\n", $output);
        if ($return != 0) {
            throw new Exception("La v�rification de la signature a �chou� (code $return):  (command : $command) (retour : $output)");
        }
    }
}
