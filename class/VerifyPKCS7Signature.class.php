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
			$signature_file = sys_get_temp_dir() . "/slow_signature_".mt_rand(0,mt_getrandmax());
			$certificate_file = sys_get_temp_dir() . "/slow_certificate_".mt_rand(0,mt_getrandmax());

			$this->verifyThrow($file_path,$signature,$signature_file,$certificate_file);
				
		} catch(Exception $e){
			throw $e;
		}

		unlink($certificate_file);
		unlink($signature_file);
		return true;
	}

	private function verifyThrow($file_path,$signature,$signature_file,$certificate_file){
		$result = file_put_contents($signature_file, $signature);
		if ($result === false){
			throw new Exception("Impossible d'�crire la signature dans $signature_file");
		}

		$certificate = $this->getCertificate($signature_file);


		$result = file_put_contents($certificate_file, $certificate);
		if ($result === false){
			throw new Exception("Impossible d'�crire le certificat dans $certificate_file");
		}

		$this->checkCertificate($certificate_file);

		$command ="openssl smime -in $signature_file -inform PEM -verify -content $file_path -CApath {$this->authorized_ca_path} > /dev/null 2>&1";
		exec($command, $output, $return);
		$output = implode("\n",$output);
		if ($return != 0 ){
			throw new Exception("La v�rification de la signature a �chou� (code $return):  (command : $command) (retour : $output)");
		}
	}

	public function verifyCertificate($signature_content){
		$signature_path = "/tmp/s2low_verify_pkcs7_".mt_rand(0,getrandmax());
		file_put_contents($signature_path,$signature_content);
		$certificate = $this->getCertificate($signature_path);
		$certificate_path = "/tmp/s2low_verify_pkcs7_".mt_rand(0,getrandmax());
		file_put_contents($certificate_path, $certificate);
		try {
			$this->checkCertificate($certificate_path);
		} finally {
			unlink($signature_path);
			unlink($certificate_path);
		}
	}

	private function getCertificate($signatureFileName){
		$extractCmd = "openssl pkcs7 -in " . $signatureFileName . " -print_certs | openssl x509";

		exec($extractCmd, $output, $ret);

		if ( $ret ) {
			throw new Exception("Erreur d'extraction du certificat : echec de la commande $extractCmd");
		}

		$cert = implode("\n",$output);
		$cert.="\n";

		return $cert;
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

        if($nonBlockingErrorThrown){
            if(!$this->openSslWrapper->isDateValid($certificate_path)){
                throw new Exception("Erreur lors de la verification du certificat (commande : ) (result: )");   //TODO : modify
            }
        }

		return true;
	}
}
