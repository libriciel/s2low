<?php 


class X509Certificate {
	
	public function retrieveClientInfo(){
		 // Ne marche pas avec apache-ssl
		if ( empty($_SERVER['SSL_CLIENT_VERIFY'])){
			return false;
		}
		if ($_SERVER['SSL_CLIENT_VERIFY'] != "SUCCESS") {
			return false;
		}
		
		$result['subject'] = $_SERVER['SSL_CLIENT_S_DN'];
		
		if (empty($_SERVER['SSL_CLIENT_CERT'])){
			$result['issuer'] = $_SERVER['SSL_CLIENT_I_DN'];
			return $result;
		}
		
		if (($tab = openssl_x509_parse($_SERVER['SSL_CLIENT_CERT'])) === false) {
       		return false;
        }

		$result['issuer'] = "";
        foreach ($tab['issuer'] as $key => $val) {
 	       $result['issuer'] .= "/" . $key . "=" . utf8_decode($val);
        }

		$result['subject'] = "";
		foreach ($tab['subject'] as $key => $val) {
			$result['subject'] .= "/" . $key . "=" . utf8_decode($val);
		}

		return $result;		
	}
	
	public function getExpirationDate($pem_certificate_content){
		$info = $this->getInfo($pem_certificate_content);
		if ( ! $info){
			return false;
		}
		return $info['expiration_date'];
	}
	
	public function getInfo($pem_certificate_content){
		if (! $pem_certificate_content){
			return false;
		}
		$resource = $this->readCertContent($pem_certificate_content);
		$info =  openssl_x509_parse($resource);
		$info['expiration_date'] = $this->certTime2IsoDate($info['validTo']);
		return $info;
	}


	private function readCertContent($cert_content){
		@ $resource = openssl_x509_read($cert_content);
		if (! $resource){
			throw new Exception("Impossible de lire le certificat");
		}
		return $resource;
	}

	private function certTime2IsoDate($validTo){
		preg_match_all("#(\d\d)#",$validTo,$matches);
		$m = $matches[0];
		return "20{$m[0]}-{$m[1]}-{$m[2]} {$m[3]}:{$m[4]}:{$m[5]}";
	}
	
	public function pemClean($not_clean_pem){
		$resource = $this->readCertContent($not_clean_pem);
		openssl_x509_export($resource, $output);
		return $output;
	}
	
	
}