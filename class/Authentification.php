<?php

namespace S2lowLegacy\Class;

use Exception;
use S2low\Exceptions\NoPasswordException;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Model\NounceSQL;
use S2lowLegacy\Model\UserSQL;

class Authentification
{
    public const AUTHENTIFICATION_BY_APACHE = 1;
    public const AUTHENTIFICATION_BY_FORM = 2;

    /** @var UserSQL  */
    private $userSQL;

    /** @var NounceSQL */
    private $nounceSQL;

    /** @var  Environnement */
    private $environnement;

    /** @var PasswordHandler */
    private $passwordHandler;

    /** @var HttpsConnexion  */
    private $httpsConnexion;

    public function __construct(
        Environnement $environnement,
        UserSQL $userSQL,
        PasswordHandler $passwordHandler,
        HttpsConnexion $httpsConnexion,
        ?NounceSQL $nounceSQL = null,
    ) {
        $this->environnement = $environnement;
        $this->userSQL = $userSQL;
        $this->nounceSQL = $nounceSQL;
        $this->passwordHandler = $passwordHandler;
        $this->httpsConnexion = $httpsConnexion;
    }

    /**
     * @param int $authentProcess
     * @return bool|mixed
     * @throws Exception
     */
    public function authenticate(int $authentProcess = Authentification::AUTHENTIFICATION_BY_APACHE)
    {
        if ($this->environnement->session()->get('id_login')) {
            $this->verifConnexion($this->environnement->session()->get('id_login'));
            return $this->environnement->session()->get('id_login');
        }
        $this->environnement->session()->set('id_login', $this->detectConnexionID($authentProcess));
        return $this->environnement->session()->get('id_login');
    }


    /**
     * @param int $authentProcess
     * @return array|bool|mixed
     * @throws Exception
     */
    private function detectConnexionID(int $authentProcess = Authentification::AUTHENTIFICATION_BY_APACHE)
    {
        //TODO Refactorer les Helper:redirect

        try {
            if (
                $this->httpsConnexion->hasNonceParameters()
                &&
                $this->getConnexionIdFromNounce($this->httpsConnexion->getNonceParameters())
            ) {
                return $this->getConnexionIdFromNounce($this->httpsConnexion->getNonceParameters());
            }

            $id_list = $this->getIdFromConnexionInfo($this->getAllConnexionInfo($authentProcess));
            if (empty($id_list)) {
                throw new Exception("Le certificat n'est pas valide : aucun compte trouvé");
            }
            if (count($id_list) != 1) {
                throw new Exception("La connexion n'a pas pu être établie");
            } // @codeCoverageIgnore
        } catch (NoPasswordException $e) {
            $redirect = Helpers::getLink("/login.php");
            Helpers::returnAndExit(1, $e->getMessage(), $redirect);
        } catch (Exception $e) {
            $redirect = Helpers::getLink("connexion-status");
            if ($e->getMessage() === "La connexion n'a pas pu être établie") {
                $redirect = Helpers::getLink("/login.php");
            }
            Helpers::returnAndExit(1, $e->getMessage(), $redirect);
        }

        return $id_list[0];
    }

    /**
     * @param $user_id
     * @throws Exception
     */
    private function verifConnexion($user_id)
    {
        try {
            $connexion_info = $this->getAllConnexionInfo();
        } catch (Exception $e) {
            Helpers::returnAndExit(1, "La connexion n'a pas pu être établie", Helpers::getLink("connexion-status"));
        } // @codeCoverageIgnore

        $list_id = $this->userSQL->getIdsFromConnexionInfo($connexion_info['certificate_hash']);

        if (! in_array($user_id, $list_id)) {
            Helpers::returnAndExit(1, "La connexion n'a pas pu être établie", Helpers::getLink("/login.php"));
        } // @codeCoverageIgnore
    }

    /**
     * @param int $authentProcess
     * @return array|false
     * @throws Exception
     */
    public function getAllConnexionInfo(int $authentProcess = Authentification::AUTHENTIFICATION_BY_APACHE)
    {
        if ($authentProcess === Authentification::AUTHENTIFICATION_BY_APACHE) {
            $credentials = $this->httpsConnexion->getCredentialsFromApache();
        } elseif ($authentProcess === Authentification::AUTHENTIFICATION_BY_FORM) {
            $credentials = $this->httpsConnexion->getCredentialsFromPost();
        } else {
            throw new Exception("Méthode d'authentification non reconnue");
        }

        $certificateInfos = $this->httpsConnexion->getCertificateInfo();

        if (!$certificateInfos) {
            throw new Exception("Aucune information de certificat trouvée");
        }

        return array_merge($credentials, $certificateInfos);
    }

    private function getConnexionIdFromNounce(array $nonceParameters)
    {
        $authority_id = $this->nounceSQL->verify(
            ...$nonceParameters
        );

        if (! $authority_id) {
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
    private function getIdFromConnexionInfo(array $connexion_info)
    {
        if ($connexion_info['login']) {
            return $this->getIdFromCertificateAndLogin($connexion_info);
        }
        return $this->getIdFromCertificateOnly($connexion_info);
    }

    /**
     * @param $connexion_info
     * @return array
     */
    private function getIdFromCertificateAndLogin(array $connexion_info): array
    {
        $possibleUsersInDB = $this->userSQL->getIdsAndPasswordsFromConnexionInfo(
            $connexion_info['certificate_hash'],
            $connexion_info['login']
        );
        $ids = [];

        foreach ($possibleUsersInDB as $possibleUser) {
            if (empty($possibleUser['password'])) {
                throw new NoPasswordException();
            }

            if (
                $this->passwordHandler->passwordMatchesHash(
                    $connexion_info['password'],
                    $possibleUser["password"],
                    $possibleUser['id']
                )
            ) {
                $ids[] = $possibleUser["id"];
            }
        }

        return $ids;
    }

    /**
     * @param $connexion_info
     * @return mixed
     */
    private function getIdFromCertificateOnly(array $connexion_info)
    {
        return $this->userSQL->getIdsFromConnexionInfo(
            $connexion_info['certificate_hash'],
        );
    }
}
