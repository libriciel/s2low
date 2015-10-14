<?php

class Authentification {
	
	public static function getInstance(){
		global $sqlQuery;
		$userSQL = new UserSQL($sqlQuery);
		$authentification = new Authentification($_SERVER,$_SESSION, $userSQL);
		return $authentification;
	}
	
	private $session;
	private $server;
	private $userSQL;
	
	
	public function __construct(array $server,array $session,UserSQL $userSQL){
		$this->session = $session;
		$this->server = $server;
		$this->userSQL = $userSQL;
	}
	
	public function authenticate(){

		if (! empty($this->session['id_login'])){
			$this->verifConnexion($this->session['id_login']);
			return $this->session['id_login'];
		} else {
			$this->session['id_login'] = $this->detectConnexionID();
			$_SESSION['id_login'] = $this->session['id_login'];
		}
		
		return $this->session['id_login'];
	}
	
	private function detectConnexionID(){
		$connexion_info = $this->getAllConnexionInfo();

		$id_list = $this->userSQL->getIdFromConnexionInfo($connexion_info['subject_dn'],
											$connexion_info['issuer_dn'],
											$connexion_info['certificate_rgs_2_etoiles'], $connexion_info['login'], $connexion_info['password']);

		if (count($id_list) != 1){
			Helpers::returnAndExit(1, "La connexion n'a pas pu être établie",  WEBSITE_SSL."/login.php");
		} // @codeCoverageIgnore
		
		return $id_list[0];
	}
	
	private function verifConnexion($user_id){
		$connexion_info = $this->getAllConnexionInfo();
		if (! $connexion_info){
			Helpers::returnAndExit(1, "La connexion n'a pas pu être établie",  WEBSITE);
		} // @codeCoverageIgnore
		$list_id = $this->userSQL->getListIdFromConnexion($connexion_info['subject_dn'],$connexion_info['issuer_dn'],$connexion_info['certificate_rgs_2_etoiles']);
		
		if (! in_array($user_id,$list_id)){
			Helpers::returnAndExit(1, "La connexion n'a pas pu être établie",  WEBSITE_SSL."/login.php");
		} // @codeCoverageIgnore
	}
	
	
	private function getAllConnexionInfo(){
		//http://stackoverflow.com/a/18205049
		if (function_exists('apache_request_headers')) {
			$h = apache_request_headers();
			if (isset($h['org.s2low.forward-x509-identification'])) {
				$this->server['HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION'] = $h['org.s2low.forward-x509-identification'];
			}
		}
		$result = array();
		foreach(array('SSL_CLIENT_VERIFY' => 'ssl_client_verify',
				'SSL_CLIENT_S_DN' => 'subject_dn',
				'SSL_CLIENT_I_DN'=>'issuer_dn',
				'SSL_CLIENT_CERT'=>'ssl_client_cert',
				'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION'=>'certificate_rgs_2_etoiles',
				'PHP_AUTH_USER'=>'login',
				'PHP_AUTH_PW'=>'password') as $server_key => $result_key){
					
				if (empty($this->server[$server_key])){
					$result[$result_key] = false;
				} else {
					$result[$result_key] = $this->server[$server_key];
				}
		}
	
		if (! $result['ssl_client_verify']){
			return false;
		}
	
		if ($result['ssl_client_cert']){
			if (($tab = openssl_x509_parse($result['ssl_client_cert'])) === false) {
				return false;
			}
			$result['issuer_dn'] = '';
			foreach ($tab['issuer'] as $key => $val) {
				$result['issuer_dn'] .= "/" . $key . "=" . utf8_decode($val);
			}
			$result['subject_dn'] = "";
			foreach ($tab['subject'] as $key => $val) {
				$result['subject_dn'] .= "/" . $key . "=" . utf8_decode($val);
			}
		}
		if ($result['certificate_rgs_2_etoiles']){
			$result['certificate_rgs_2_etoiles'] = $this->der2pem(base64_decode($result['certificate_rgs_2_etoiles']));
		}

		return $result;
	}
	
	private function der2pem($der_data) {
		$pem = chunk_split(base64_encode($der_data), 64, "\n");
		$pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
		return $pem;
	}
}