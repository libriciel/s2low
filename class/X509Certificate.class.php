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
		$result['issuer'] = $_SERVER['SSL_CLIENT_I_DN'];
		return $result;		
	}
	
	public function getExpirationDate($certificateContentPEM){
		$info = openssl_x509_parse(openssl_x509_read($certificateContentPEM));
		preg_match_all("#(\d\d)#",$info['validTo'],$matches);
		$m = $matches[0];
		return "20{$m[0]}-{$m[1]}-{$m[2]} {$m[3]}:{$m[4]}:{$m[5]}";
		
	}
	
}