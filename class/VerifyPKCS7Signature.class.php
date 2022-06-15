<?php
class VerifyPKCS7Signature {

    /** @var \VerifyPemCertificate  */
    private $verifyPemCertificate;
    /** @var string */
    private $authorized_ca_path;
    /** @var \PemCertificateFactory */
    private $pemCertificateFactory;


    public function __construct(string $authorized_ca_path,
                                VerifyPemCertificateFactory $verifyPemCertificateFactory,
                                PemCertificateFactory $pemCertificateFactory
    ){
        $this->verifyPemCertificate = $verifyPemCertificateFactory->get($authorized_ca_path);
        $this->authorized_ca_path = $authorized_ca_path;
        $this->pemCertificateFactory = $pemCertificateFactory;
	}

    /**
     * @throws \Exception
     */
    public function verify($file_path, $signature, DateTime $dateTime =null) : bool
    {
        if(is_null($dateTime)){
            $dateTime=new DateTime();
        }
		try {
			$signature_file = sys_get_temp_dir() . "/slow_signature_".mt_rand(0,mt_getrandmax());
			$certificate_file = sys_get_temp_dir() . "/slow_certificate_".mt_rand(0,mt_getrandmax());

			$this->verifyThrow($file_path,$signature,$signature_file,$certificate_file,$dateTime);
				
		} catch(Exception $e){
				
			if (isset($certificate_file) && file_exists($certificate_file)) {
				unlink($certificate_file);
			}
			if (isset($signature_file) && file_exists($signature_file)){
				unlink($signature_file);
			}
			throw $e;
		}

		unlink($certificate_file);
		unlink($signature_file);
		return true;
	}

    /**
     * @throws \Exception
     */
    private function verifyThrow($file_path, $signature, $signature_file, $certificate_file,DateTime $dateTime){
		$result = file_put_contents($signature_file, $signature);
		if ($result === false){
			throw new Exception("Impossible d'écrire la signature dans $signature_file");
		}

		$certificate = $this->getCertificate($signature_file);


		$result = file_put_contents($certificate_file, $certificate);
		if ($result === false){
			throw new Exception("Impossible d'écrire le certificat dans $certificate_file");
		}
        $this->pemCertificateFactory->getFromString($certificate)->checkCertificateIsValidAtDate($dateTime);
		$this->verifyPemCertificate->checkCertificateWithOpenSSL($certificate_file,[],$dateTime->getTimestamp());
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

    /**
     * @throws \Exception
     */
    public function verifyCertificate(string $signature_content){
		$signature_path = "/tmp/s2low_verify_pkcs7_".mt_rand(0,getrandmax());
		file_put_contents($signature_path,$signature_content);
		$certificate = $this->getCertificate($signature_path);
		$certificate_path = "/tmp/s2low_verify_pkcs7_".mt_rand(0,getrandmax());
		file_put_contents($certificate_path, $certificate);
		try {
		    $this->pemCertificateFactory->getFromString($certificate)->checkCertificateIsValidAtDate(new DateTime());
			$this->verifyPemCertificate->checkCertificateWithOpenSSL(
			    $certificate_path,
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS
            );
		} finally {
			unlink($signature_path);
			unlink($certificate_path);
		}
	}

    /**
     * @throws \Exception
     */
    private function getCertificate(string $signatureFileName) : string
    {
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