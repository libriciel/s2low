<?php

class Authentification {

    const AUTHENTIFICATION_BY_APACHE=1;
    const AUTHENTIFICATION_BY_FORM=2;

	private $userSQL;

	/** @var NounceSQL */
	private $nounceSQL;

	/** @var  Environnement */
	private $environnement;

	/** @var PasswordHandler */
    private $passwordHandler;

    /** @var S2lowLogger  */
    private $logger;

    public function __construct(
        Environnement $environnement,
		UserSQL $userSQL,
        PasswordHandler $passwordHandler,
		NounceSQL $nounceSQL=null,
        S2lowLogger $logger,
        HttpsConnexion $httpsConnexion
	){
		$this->environnement = $environnement;
		$this->userSQL = $userSQL;
		$this->nounceSQL = $nounceSQL;
		$this->passwordHandler = $passwordHandler;
		$this->logger = $logger;
		$this->httpsConnexion = $httpsConnexion;
	}

	/**
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function authenticate($authentProcess=Authentification::AUTHENTIFICATION_BY_APACHE){
	    $this->logger->error("authenticate");
		if ($this->environnement->session()->get('id_login')){
			$this->verifConnexion($this->environnement->session()->get('id_login'));
			return $this->environnement->session()->get('id_login');
		} else {
            $this->environnement->session()->set('id_login',$this->detectConnexionID($authentProcess));
		}
		return $this->environnement->session()->get('id_login');
	}

	/**
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	private function detectConnexionID($authentProcess=Authentification::AUTHENTIFICATION_BY_APACHE) {
        //TODO Refactorer les Helper:redirect

        if ($this->httpsConnexion->hasNonceParameters()){
            if ($this->getConnexionIdFromNounce($this->httpsConnexion->getNonceParameters())){
                return $this->getConnexionIdFromNounce($this->httpsConnexion->getNonceParameters());
            }
        }

        $id_list = $this->getIdFromConnexionInfo($this->httpsConnexion->getAllConnexionInfo($authentProcess));

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
		$connexion_info = $this->httpsConnexion->getAllConnexionInfo();
		if (! $connexion_info){
			Helpers::returnAndExit(1, "La connexion n'a pas pu être établie",  WEBSITE);
		} // @codeCoverageIgnore
		$list_id = $this->userSQL->getListIdFromConnexion($connexion_info['certificate_hash'], $connexion_info['certificate_rgs_2_etoiles']);

		if (! in_array($user_id,$list_id)){
			Helpers::returnAndExit(1, "La connexion n'a pas pu être établie",  WEBSITE_SSL."/login.php");
		} // @codeCoverageIgnore
	}

	private function getConnexionIdFromNounce($nonceParameters){
        $authority_id = $this->nounceSQL->verify(
			...$nonceParameters
		);

		if(! $authority_id){
			return false;
		}

		return $this->userSQL->getIdFromCertificateAndAuthority(
		    $this->httpsConnexion->getCertificateHash(),
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
            return $this->getIdFromCertificateAndLogin($connexion_info);
        }
        return $this->getIdFromCertificateOnly($connexion_info);
    }

    /**
     * @param $connexion_info
     * @return array
     */
    private function getIdFromCertificateAndLogin($connexion_info): array
    {
        $possibleUsersInDB = $this->userSQL->getIdsAndPasswordsFromConnexionInfo(
            $connexion_info['certificate_hash'],
            $connexion_info['certificate_rgs_2_etoiles'],
            $connexion_info['login']
        );
        $ids = [];

        foreach ($possibleUsersInDB as $possibleUser) {
            if ($this->passwordHandler->passwordMatchesHash(
                $connexion_info['password'],
                $possibleUser["password"],
                $possibleUser['id']
            )) {
                $ids[] = $possibleUser["id"];
            }
        }

        return $ids;
    }

    /**
     * @param $connexion_info
     * @return mixed
     */
    private function getIdFromCertificateOnly($connexion_info)
    {
        $idsFromConnexionInfo = $this->userSQL->getIdsFromConnexionInfo(
            $connexion_info['certificate_hash'],
            $connexion_info['certificate_rgs_2_etoiles']
        );
        return $idsFromConnexionInfo;
    }
}