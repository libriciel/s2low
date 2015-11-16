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
		$info['issuer_name'] = "";
		foreach ($info['issuer'] as $key => $val) {
			$info['issuer_name'] .= "/" . $key . "=" . utf8_decode($val);
		}

		$info['subject_name'] = "";
		foreach ($info['subject'] as $key => $val) {
			$info['subject_name'] .= "/" . $key . "=" . utf8_decode($val);
		}
		return $info;
	}

	public function getIssuerDN($pem_certificate_content){
		$info = $this->getInfo($pem_certificate_content);

		$issuerName = "";
		foreach(array_reverse($info['issuer']) as $document_id => $value){
			$issuerName[] = "$document_id=$value";
		}
		return implode(", ",$issuerName);
	}

	public function getBase64Hash($cert_content, $hash_alg = 'sha1')
	{
		$tmp_file = sys_get_temp_dir()."/".uniqid("x509_pem");
		file_put_contents($tmp_file,$cert_content);

		$command = "openssl x509 -in $tmp_file -outform der | openssl $hash_alg -binary | openssl base64";
		exec($command,$output,$return_var);
		$certDigest = $output[0];
		unlink($tmp_file);
		return $certDigest;
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