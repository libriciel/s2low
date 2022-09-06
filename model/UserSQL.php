<?php

namespace S2lowLegacy\Model;

use S2lowLegacy\Lib\SQL;
use S2lowLegacy\Lib\X509Certificate;

class UserSQL extends SQL
{
    public const IDENT_METHOD_NONE = 0;
    public const IDENT_METHOD_CERT_ONLY = 1;
    public const IDENT_METHOD_LOGIN = 2 ;

    /** @deprecated 4.0.3 */
    public const IDENT_METHOD_RGS_2_ETOILES = 3;

    public const STATUS_DESACTIVE = 0;
    public const STATUS_ACTIVE = 1;

    public const CERTIFICATE_FINGERPRINT_HASH_ALG = 'sha1';

    public const AUTHORITY_ID = 'authority_id';

    public function getPrettyName($name, $givenname, $login)
    {
        return $name ? "$givenname $name" : $login;
    }

    public function getInfo($id)
    {
        if (!$id) {
            return array();
        }
        $sql = "SELECT * FROM users WHERE id=?";
        $result = $this->queryOne($sql, $id);
        if (! $result) {
            return array();
        }
        $result['pretty_name'] = $this->getPrettyName($result['name'], $result['givenname'], $result['login']);
        $result['role_str'] = $this->getRoleStr($result['role']);
        $result['nb_user_with_my_certificate'] = $this->getNbUserWithMyCertificate($result['certificate_hash']);
        return $result;
    }

    public function getNbUserWithMyCertificate($certificate_hash)
    {
        $sql = "SELECT count(*) as nb FROM users " .
            " WHERE certificate_hash = ? ";
        return $this->queryOne($sql, $certificate_hash);
    }

    public function getInfoFromCertificateInfo(array $certificateInfo)
    {
        $sql = "SELECT * FROM users " .
            " WHERE certificate_hash=? " .
                " ORDER BY id ";
        return $this->query($sql, $certificateInfo['certificate_hash']);
    }

    public function getIdListFromCertificateInfo($certificate_hash)
    {
        $sql = "SELECT id FROM users WHERE certificate_hash=? ";
        return $this->queryOneCol($sql, $certificate_hash);
    }

    public function getListFromCertificateInfo($certificate_hash)
    {
        $sql = "SELECT users.id,login,users.name,givenname,users.email,users.status,role,authority_id, authorities.name as authority_name FROM users " .
            " JOIN authorities ON users.authority_id = authorities.id " .
            " WHERE certificate_hash=? " .
            " ORDER BY login ";
        return $this->query($sql, $certificate_hash);
    }


    public function getRoleStr($role)
    {
        $roleTypes = array( "SADM" => "Super administrateur",
                               "GADM" => "Administrateur de groupe",
                               "ADM" => "Administrateur collectivité",
                               "USER" => "Utilisateur"
                               );
        return $roleTypes[$role];
    }

    public function getDIAUser($authority_id)
    {
        $sql = "SELECT users.id as user_id FROM users " .
                " JOIN users_perms ON users.id = users_perms.user_id " .
                " JOIN modules ON users_perms.module_id = modules.id " .
                " WHERE authority_id=? AND users_perms.perm='RW' AND modules.name='dia'";
        return $this->query($sql, $authority_id);
    }

    public function getIdentificationMethod($user_id)
    {
        $info = $this->getInfo($user_id);
        if (!$info) {
            return self::IDENT_METHOD_NONE;
        }
        if ($info['certificate_rgs_2_etoiles']) {
            return self::IDENT_METHOD_RGS_2_ETOILES;
        }

        $user_id_list = $this->getIdListFromCertificateInfo($info['certificate_hash']);
        if (count($user_id_list) == 1 && ! $info['login']) {
            return self::IDENT_METHOD_CERT_ONLY;
        }
        return self::IDENT_METHOD_LOGIN;
    }

    public function getIdentificatonMethodeList()
    {
        return array(
            self::IDENT_METHOD_CERT_ONLY => 'Certificat à usage individuel',
            self::IDENT_METHOD_LOGIN => 'Certificat partagé et login/mot de passe'
        );
    }

    public function getIdentificationMethodeLibelle($ident_method_id)
    {
        if (!$ident_method_id) {
            $ident_method_id = 1;
        }
        $libelle = $this->getIdentificatonMethodeList();
        return $libelle[$ident_method_id];
    }

    public function saveCertificateRGS2Etoiles($user_id, $pem_certificate_content)
    {
        $sql = "UPDATE users SET certificate_rgs_2_etoiles=? WHERE id=?";
        $this->query($sql, $pem_certificate_content, $user_id);
    }


    public function deleteCertificateRGS2Etoiles($user_id)
    {
        $this->saveCertificateRGS2Etoiles($user_id, "");
    }

    //Hack affreux pour prévenir les NULL introduit par le DataObject !
    public function updateCertificatRGS2EtoilesIfNull($user_id)
    {
        $sql = "SELECT * FROM users WHERE id=? AND certificate_rgs_2_etoiles IS NULL";
        if ($this->queryOne($sql, $user_id)) {
            $this->saveCertificateRGS2Etoiles($user_id, '');
        }
    }

    public function getIdsAndPasswordsFromConnexionInfo($certificate_hash, $certificate_rgs_2_etoile, $login)
    {
        $sql = "SELECT id,password FROM users " .
            " WHERE certificate_hash=? " .
                " AND certificate_rgs_2_etoiles = ?  AND login=?  ORDER BY id ";

        $data = array($certificate_hash, $certificate_rgs_2_etoile,$login);

        return $this->query($sql, $data);
    }

    public function setPassword(int $userId, string $passwordHash): void
    {
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $data = [$passwordHash,$userId];
        $this->query($sql, $data);
    }

    public function getIdsFromConnexionInfo(string $certificate_hash, string $certificate_rgs_2_etoile): array
    {
        $sql = "SELECT id FROM users " .
            " WHERE certificate_hash=? " .
            " AND certificate_rgs_2_etoiles = ?  ORDER BY id ";

        $data = array($certificate_hash, $certificate_rgs_2_etoile);

        return $this->queryOneCol($sql, $data);
    }

    public function getListIdFromConnexion($certificate_hash, $certificate_rgs_2_etoile)
    {
        $sql = "SELECT id FROM users " .
            " WHERE certificate_hash = ? " .
            " AND certificate_rgs_2_etoiles = ? " .
            " ORDER BY id ";
        return $this->queryOneCol($sql, $certificate_hash, $certificate_rgs_2_etoile);
    }

    public function getGroupeName($user_id)
    {
        $sql = "SELECT authority_groups.name FROM users JOIN authority_groups ON users.authority_group_id= authority_groups.id WHERE users.id=?";
        return $this->queryOne($sql, $user_id);
    }

    public function hasDoublon($user_id, $certificat_connexion_info, $login, $certificate_rgs_2_etoiles_clean_content)
    {

        if ($certificate_rgs_2_etoiles_clean_content) {
            $sql = "SELECT id FROM users WHERE certificate_hash=? AND certificate_rgs_2_etoiles=?";
            $result = $this->queryOneCol($sql, $certificat_connexion_info['certificate_hash'], $certificate_rgs_2_etoiles_clean_content);
        } elseif ($login) {
            $sql = "SELECT id FROM users WHERE certificate_hash=? AND login=?";
            $result = $this->queryOneCol($sql, $certificat_connexion_info['certificate_hash'], $login);
        } else {
            $sql = "SELECT id FROM users WHERE certificate_hash=? ";
            $result = $this->queryOneCol($sql, $certificat_connexion_info['certificate_hash']);
        }

        if (! $result) {
            return false;
        }
        if (count($result) > 1) {
            return true;
        }
        return ($result[0] != $user_id);
    }

    public function fixCerticateFingerprint(X509Certificate $x509Certificate)
    {
        $result = $this->query("SELECT id,certificate FROM users ");
        $query = "UPDATE users SET certificate_hash = ? WHERE id=?";
        foreach ($result as $line) {
            if ($line['certificate']) {
                $hash = $x509Certificate->getBase64Hash($line['certificate'], self::CERTIFICATE_FINGERPRINT_HASH_ALG);
                $this->query($query, $hash, $line['id']);
            }
        }
    }

    public function getIdFromCertificateAndAuthority($certificate_hash, $authority_id)
    {
        $sql = "SELECT id FROM users " .
            " WHERE certificate_hash = ? " .
            " AND authority_id = ? " .
            " ORDER BY id LIMIT 1";
        return $this->queryOne($sql, $certificate_hash, $authority_id);
    }

    public function updateMail(int $id, string $newMail)
    {
        return $this->queryOne(
            "UPDATE users SET email=? WHERE id=?",
            $newMail,
            $id
        );
    }
}
