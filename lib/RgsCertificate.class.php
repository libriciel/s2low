<?php

class RgsCertificate {

	private $openssl_path;
	private $validca_path;
	private $last_message;

	/**
	 * @param $openssl_path string chemin vers l'executable OpenSSL
	 * @param $validca_path string chemin vers un répertoire contenant des autorités de certification "hasher" : man c_rehash
	 */
	public function __construct($openssl_path,$validca_path){
		$this->validca_path = $validca_path;
		$this->openssl_path = $openssl_path;
	}

	public function getLastMessage(){
		return $this->last_message;
	}

	public function isRgsCertificate($x509_pem_certificate,$clientCertChain =null){
        $indice = mt_rand(0, mt_getrandmax());
        $tmp_cert = "/tmp/s2low-lib-rgscertificate-". $indice .".pem";
		file_put_contents($tmp_cert,$x509_pem_certificate);

		if(!is_null($clientCertChain)) {
            $tmp_chain = "/tmp/s2low-lib-certchain-" . $indice . ".pem";
            file_put_contents($tmp_chain, $clientCertChain);

            $command = "{$this->openssl_path} verify -verbose -CApath {$this->validca_path} -untrusted {$tmp_chain} {$tmp_cert} 2>&1";
        }
		else {
            $command = "{$this->openssl_path} verify -verbose -CApath {$this->validca_path} {$tmp_cert} 2>&1";
        }


		//Il semble qu'il n'y a pas de fonction php openssl_* qui permettent la vérification d'un certificat

		exec($command,$output,$return_var);
		unlink($tmp_cert);
		if(isset($tmp_chain)) {
            unlink($tmp_chain);
        }


		$output = implode("\n",$output);

		if (preg_match("#{$tmp_cert}: OK#",$output)){
			$result = true;
		} else {
			$result = false;
			$this->last_message = $output;
		}

		return $result;
	}

}