<?php
class VerifyPKCS7Signature {

	private $authorized_ca_path;

	public function __construct($authorized_ca_path){
		$this->authorized_ca_path = $authorized_ca_path;
	}

	public function verify($file_path,$signature){
		try {
			$signature_file = sys_get_temp_dir() . "/slow_signature_".mt_rand(0,mt_getrandmax());
			$certificate_file = sys_get_temp_dir() . "/slow_certificate_".mt_rand(0,mt_getrandmax());

			$this->verifyThrow($file_path,$signature,$signature_file,$certificate_file);
				
		} catch(Exception $e){
			throw $e;
				
			if (file_exists($certificate_file)) {
				unlink($certificate_file);
			}
			if (file_exists($signature_file)){
				unlink($signature_file);
			}
			throw $e;
		}

		unlink($certificate_file);
		unlink($signature_file);
		return true;
	}

	private function verifyThrow($file_path,$signature,$signature_file,$certificate_file){
		$result = file_put_contents($signature_file, $signature);
		if ($result === false){
			throw new Exception("Impossible d'écrire la signature dans $signature_file");
		}

		$certificate = $this->getCertificate($signature_file);


		$result = file_put_contents($certificate_file, $certificate);
		if ($result === false){
			throw new Exception("Impossible d'écrire le certificat dans $certificate_file");
		}

		$this->checkCertificate($certificate_file);

		$command ="openssl smime -in $signature_file -inform PEM -verify -content $file_path -CApath {$this->authorized_ca_path} > /dev/null 2>&1";
		exec($command, $output, $return);
		$output = implode("\n",$output);
		if ($return != 0 ){
			throw new Exception("La vérification de la signature a échoué (code $return):  (command : $command) (retour : $output)");
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
		$verifyCmd = "openssl verify -CApath {$this->authorized_ca_path} -crl_check $certificate_path 2>&1";
		exec($verifyCmd, $out, $ret);

		$result = implode("\n",$out);

		# extracted from https://github.com/openssl
        # Mise en correspondance de  openssl/crypto/x509/x509_txt.c
        # et https://docs.huihoo.com/doxygen/openssl/1.0.1c/crypto_2x509_2x509__vfy_8h.html
        $nonBlockingVerifyErrors = [
            2,  # unable to get issuer certificate
            3,  # unable to get certificate CRL
            18, # self signed certificate
            19, # self signed certificate in certificate chain
            20, # unable to get local issuer certificate
            21, # unable to verify the first certificate
        ];

        $blockingKnownVerifyErrors = [
            10=>"certificate has expired",
            23=>"certificate revoked"
        ];


        $nonBlockingErrorThrown = false;
        foreach ($out as $line) {
            if (preg_match("/error ([0123456789]+) at ([0123456789])+ depth lookup:(.*)/", $line, $matches)) {
                if (!in_array($matches[1], $nonBlockingVerifyErrors)) {
                    if(in_array($matches[1], array_keys($blockingKnownVerifyErrors))){
                        $message = $blockingKnownVerifyErrors[$matches[1]];
                    } else {
                        $message = "Erreur #{$matches[1]} lors de la verification du certificat (commande : $verifyCmd) (result: $result)";
                    }
                    throw new Exception($message);
                } else {
                    $nonBlockingErrorThrown = true;
                }
            }
        }

        if($nonBlockingErrorThrown){
            // La date n'est alors pas forcément vérifiée par openssl...
            // Copié - collé depuis PadesValid => REFACTO NECESSAIRE
            $x509_info = openssl_x509_parse(file_get_contents($certificate_path));  #Moche

            $dateValidFrom = new DateTime(date(DATE_RFC2822,$x509_info['validFrom_time_t']));
            $dateValidTo = new DateTime(date(DATE_RFC2822,$x509_info['validTo_time_t']));
            $dateNow = new DateTime('NOW');

            if ($dateNow < $dateValidFrom || $dateNow > $dateValidTo ) {
                throw new Exception("certificate has expired");
            }
        }

            return true;
	}

}