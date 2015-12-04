<?php

//Liste des clients qui n'ont pas un certificat RGS

require_once( __DIR__."/../../init/init.php");

if (! class_exists("RgsCertificate")){

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
}


$rgsCertificate = new RgsCertificate(OPENSSL_PATH,RGS_VALIDCA_PATH);

$handle = fopen("php://output","w");

$sql = "SELECT users.id,certificate,givenname,users.name,users.email FROM users ".
		" JOIN authorities ON users.authority_id.authorities.id ".
	" ORDER BY id";

foreach($sqlQuery->query($sql) as $line){
	if (! $rgsCertificate->isRgsCertificate($line['certificate'])){
		unset($line['certificate']);
		fputcsv($handle,$line);
	}
}

fclose($handle);

