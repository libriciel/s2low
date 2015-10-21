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

	public function isRgsCertificate($x509_pem_certificate){
		//Il semble qu'il n'y a pas de fonction php openssl_* qui permettent la vérification d'un certificat
		$command = "echo '$x509_pem_certificate' | {$this->openssl_path} verify -verbose -CApath {$this->validca_path} 2>&1";
		exec($command,$output,$return_var);

		$output = implode("\n",$output);

		if (preg_match("#stdin: OK#",$output)){
			$result = true;
		} else {
			$result = false;
			$this->last_message = $output;
		}

		return $result;
	}

}