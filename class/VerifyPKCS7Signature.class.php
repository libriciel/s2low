<?php
class VerifyPKCS7Signature {

	private $authorized_ca_path;

	public function __construct($authorized_ca_path){
		$this->authorized_ca_path = $authorized_ca_path;
        $this->verifyPemCertificate = new VerifyPemCertificate($authorized_ca_path);       //TODO : use injection
	}

	public function verify($file_path,$signature){
		try {
			$signature_file = sys_get_temp_dir() . "/slow_signature_".mt_rand(0,mt_getrandmax());
			$certificate_file = sys_get_temp_dir() . "/slow_certificate_".mt_rand(0,mt_getrandmax());

			$this->verifyThrow($file_path,$signature,$signature_file,$certificate_file);
				
		} catch(Exception $e){
				
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

		$this->verifyPemCertificate->checkCertificate($certificate_file);

		# On ne va pas vérifier le certificat (option -noverify)
        # Au niveau du purpose, smime est trop restrictif par rapport à notre besoin
        # Au niveau de la date et de la chaine de certification, on va se reposer sur
        # la fonction précédente
		$command ="openssl smime -in $signature_file -inform PEM -verify -noverify -content $file_path -CApath {$this->authorized_ca_path} > /dev/null 2>&1";
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
			$this->verifyPemCertificate->checkCertificateWithoutCheckingCertificateChain($certificate_path);
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
}