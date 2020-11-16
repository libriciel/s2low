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

		$revoked = false;

		$result = implode("\n",$out);

		/*if ($ret != 0) {    // NOUVEAU : On ne peut pas s'arrêter pour toutes les erreurs ...
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
            var_dump($line);
            if (preg_match("/error ([0123456789]+) at ([0123456789])+ depth lookup:(.*)/", $line, $matches)) {

                echo "Erreur ! : ".$matches[1]." : ";
                if (!in_array($matches[1], $nonBlockingVerifyErrors)) {
                    echo "Exception\n";
                    throw new Exception("Erreur #{$matches[1]} lors de la verification du certificat (commande : $verifyCmd) (result: $result)");
                } else {
                    echo "Exception non bloquante\n";
                    $nonBlockingErrorThrown = true;
                }
            }
        }

        if($nonBlockingErrorThrown){
            // La date n'est alors pas forcément vérifiée par openssl...
            // Copié - collé depuis PadesValid => REFACTO NECESSAIRE
            $x509_info = openssl_x509_parse(file_get_contents($certificate_path));

            //var_dump($x509_info);
            try{
                $dateValidFrom = new DateTime(date(DATE_RFC2822,$x509_info['validFrom_time_t']));
                $dateValidTo = new DateTime(date(DATE_RFC2822,$x509_info['validTo_time_t']));
                $dateNow = new DateTime('NOW');
            } catch (Exception $e){
                var_dump($e->getMessage());
            }

            $intervall = date_diff($dateValidFrom,$dateValidTo);

            echo $intervall->format('%R%a days');

            /*var_dump(date(DATE_RFC2822,$x509_info['validFrom_time_t']));
            var_dump(date(DATE_RFC2822,$x509_info['validTo_time_t']));
            var_dump(date(DATE_RFC2822,$x509_info['validFrom_time_t']) == date(DATE_RFC2822,$x509_info['validFrom_time_t']));
            var_dump(date(DATE_RFC2822,$x509_info['validTo_time_t']) > date(DATE_RFC2822,$x509_info['validFrom_time_t']));*/

            if ($dateNow < $dateValidFrom ||
                $dateNow > $dateValidTo
            ) {
                throw new Exception("La date de vérification" .
                    " n'entre pas dans la date de validité du certitficat {$x509_info['validFrom_time_t']} - {$x509_info['validTo_time_t']}");
            }
        }


            return true;
	}

}