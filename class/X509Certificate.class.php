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
	
}