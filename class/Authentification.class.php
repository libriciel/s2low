<?php

class Authentification {

	private $userSQL;

	/** @var NounceSQL */
	private $nounceSQL;

	/** @var  Environnement */
	private $environnement;

	/** @var PasswordHandler */
    private $passwordHandler;

    public function __construct(
        Environnement $environnement,
		UserSQL $userSQL,
        PasswordHandler $passwordHandler,
		NounceSQL $nounceSQL=null
	){
		$this->environnement = $environnement;
		$this->userSQL = $userSQL;
		$this->nounceSQL = $nounceSQL;
		$this->passwordHandler = $passwordHandler;
	}

	/**
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function authenticate(){
		if ($this->environnement->session()->get('id_login')){
			$this->verifConnexion($this->environnement->session()->get('id_login'));
			return $this->environnement->session()->get('id_login');
		} else {
            $this->environnement->session()->set('id_login',$this->detectConnexionID());
		}
		
		return $this->environnement->session()->get('id_login');
	}

	/**
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	private function detectConnexionID() {
        //TODO Refactorer les Helper:redirect

		$connexion_info = $this->getAllConnexionInfo();
		$id = $this->getConnexionIdFromNounce($connexion_info);
		if ($id){
			return $id;
		}


        $id_list = $this->getIdFromConnexionInfo($connexion_info);

        if (count($id_list) == 0){
			Helpers::returnAndExit(1, "Le certificat n'est pas valide : aucun compte trouvé",  WEBSITE);
		}

		if (count($id_list) != 1){
			Helpers::returnAndExit(1, "La connexion n'a pas pu être établie",  WEBSITE_SSL."/login.php");
		} // @codeCoverageIgnore
		
		return $id_list[0];
	}

	/**
	 * @param $user_id
	 * @throws Exception
	 */
	private function verifConnexion($user_id) {
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
				$this->environnement->server()->set('HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION',$h['org.s2low.forward-x509-identification']);
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
					
				if (! $this->environnement->server()->get($server_key)){
					$result[$result_key] = false;
				} else {
					$result[$result_key] = $this->environnement->server()->get($server_key);
				}
		}

		if (! $result['ssl_client_verify']){
			return false;
		}

		if ($result['ssl_client_cert']){
			$x509 = new X509Certificate();
			$info = $x509->getInfo($result['ssl_client_cert']);
			if (! $info){
				return false;
			}
			$result['issuer_dn'] = $info['issuer_name'];
			$result['subject_dn'] = $info['subject_name'];
			$result['certificate_hash'] = $info['certificate_hash'];
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
		if (empty($this->environnement->get()->get('nounce'))){
			return false;
		}
		$authority_id = $this->nounceSQL->verify(
			$this->environnement->get()->get('login'),
            $this->environnement->get()->get('nounce'),
            $this->environnement->get()->get('hash')
		);

		if(! $authority_id){
			return false;
		}

		return $this->userSQL->getIdFromCertificateAndAuthority(
			$connexion_info['certificate_hash'],
			$authority_id
		);
	}

    /**
     * @param $connexion_info
     * @return mixed
     */
    private function getIdFromConnexionInfo( $connexion_info)
    {
        if($connexion_info['login']){
            $idsAndPasswords = $this->userSQL->getIdsAndPasswordsFromConnexionInfo(
                $connexion_info['certificate_hash'],
                $connexion_info['certificate_rgs_2_etoiles'],
                $connexion_info['login']
            );
            $ids = [];

            foreach ($idsAndPasswords as $idandPassword){
                if($this->passwordHandler->passwordMatchesHash(
                    $connexion_info['password'],
                    $idandPassword["password"],
                    $idandPassword['id']
                )){
                    $ids[]=$idandPassword["id"];
                }
            }

            return $ids;

        }
        return $this->userSQL->getIdsFromConnexionInfo(
            $connexion_info['certificate_hash'],
            $connexion_info['certificate_rgs_2_etoiles']
        );
    }
    
}