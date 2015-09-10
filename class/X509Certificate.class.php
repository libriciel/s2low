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
		return $result;		
	}
	
	public function getExpirationDate($certificateContentPEM){
		if (! $certificateContentPEM){
			return;
		}
		$info = openssl_x509_parse(openssl_x509_read($certificateContentPEM));
		preg_match_all("#(\d\d)#",$info['validTo'],$matches);
		$m = $matches[0];
		return "20{$m[0]}-{$m[1]}-{$m[2]} {$m[3]}:{$m[4]}:{$m[5]}";
	}
	
	public function getInfo($pem_certificate_content){
		if (! $pem_certificate_content){
			return;
		}
		$info =  openssl_x509_parse(openssl_x509_read($pem_certificate_content));
		preg_match_all("#(\d\d)#",$info['validTo'],$matches);
		$m = $matches[0];
		$info['expiration_date'] = "20{$m[0]}-{$m[1]}-{$m[2]} {$m[3]}:{$m[4]}:{$m[5]}";
		return $info;
	}
	
	public function pemClean($not_clean_pem){

		@ $ressource = openssl_x509_read($not_clean_pem);
		if (! $ressource){
			throw new Exception("Impossible de lire le certificat");
		} 
		openssl_x509_export($ressource, $output);
		return $output;
	}
	
	
}