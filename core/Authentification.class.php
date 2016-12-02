<?php

class Authentification {
	
	public static function getInstance(){
		global $sqlQuery;
		$userSQL = new UserSQL($sqlQuery);
		$nounceSQL = new NounceSQL($sqlQuery,new PasswordGenerator());
		$authentification = new Authentification($_SERVER,$_SESSION, $userSQL,$_GET,$nounceSQL);
		return $authentification;
	}
	
	private $session;
	private $server;
	private $userSQL;
	private $get;

	/** @var NounceSQL */
	private $nounceSQL;
	
	public function __construct(
		array $server,
		array $session,
		UserSQL $userSQL,
		array $get=array(),
		NounceSQL $nounceSQL=null
	){
		$this->session = $session;
		$this->server = $server;
		$this->userSQL = $userSQL;
		$this->get = $get;
		$this->nounceSQL = $nounceSQL;
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

	public function detectConnexionID() {

		$connexion_info = $this->getAllConnexionInfo();
		$id = $this->getConnexionIdFromNounce($connexion_info);
		if ($id){
			return $id;
		}

		$id_list = $this->userSQL->getIdFromConnexionInfo(
			$connexion_info['certificate_hash'],
			$connexion_info['certificate_rgs_2_etoiles'],
			$connexion_info['login'],
			$connexion_info['password']
		);

		if (count($id_list) == 0){
			Helpers::returnAndExit(1, "Le certificat n'est pas valide : aucun compte trouvé",  WEBSITE);
		}

		if (count($id_list) != 1){
			Helpers::returnAndExit(1, "La connexion n'a pas pu être établie",  WEBSITE_SSL."/login.php");
		} // @codeCoverageIgnore
		
		return $id_list[0];
	}

	public function verifConnexion($user_id) {
		$connexion_info = $this->getAllConnexionInfo();
		if (! $connexion_info){
			Helpers::returnAndExit(1, "La connexion n'a pas pu être établie",  WEBSITE);
		} // @codeCoverageIgnore
		$list_id = $this->userSQL->getListIdFromConnexion($connexion_info['certificate_hash'], $connexion_info['certificate_rgs_2_etoiles']);

		if (! in_array($user_id,$list_id)){
			Helpers::returnAndExit(1, "La connexion n'a pas pu être établie",  WEBSITE_SSL."/login.php");
		} // @codeCoverageIgnore
	}

	public function getAllConnexionInfo() {
		//http://stackoverflow.com/a/18205049
		if (function_exists('apache_request_headers')) {
			$h = apache_request_headers();
			if (isset($h['org.s2low.forward-x509-identification'])) {
				$this->server['HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION'] = $h['org.s2low.forward-x509-identification'];
			}
		}

		$result = array();
		foreach(
				array(
					'SSL_CLIENT_VERIFY' => 'ssl_client_verify',
					'SSL_CLIENT_S_DN' => 'subject_dn',
					'SSL_CLIENT_I_DN'=>'issuer_dn',
					'SSL_CLIENT_CERT'=>'ssl_client_cert',
					'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION'=>'certificate_rgs_2_etoiles',
					'PHP_AUTH_USER'=>'login',
					'PHP_AUTH_PW' => 'password',
					'TESTING_CERTIFICATE_HASH' => 'certificate_hash',
				) as $server_key => $result_key) {
					
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

			$x509 = new X509Certificate();
			$result['certificate_hash'] = $x509->getBase64Hash($result['ssl_client_cert'], UserSQL::CERTIFICATE_FINGERPRINT_HASH_ALG);

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

	private function getConnexionIdFromNounce($connexion_info){
		if (empty($this->get['nounce'])){
			return false;
		}
		$authority_id = $this->nounceSQL->verify(
			$this->get['login'],
			$this->get['nounce'],
			$this->get['hash']
		);

		if(! $authority_id){
			return false;
		}

		return $this->userSQL->getIdFromCertificateAndAuthority(
			$connexion_info['certificate_hash'],
			$authority_id
		);
	}

}