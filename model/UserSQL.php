<?php

namespace S2lowLegacy\Model;

use S2low\Exceptions\CertificateException;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\SQL;
use S2lowLegacy\Lib\X509Certificate;
use UnexpectedValueException;

class UserSQL extends SQL
{
    public const IDENT_METHOD_NONE = 0;
    public const IDENT_METHOD_CERT_ONLY = 1;
    public const IDENT_METHOD_LOGIN = 2 ;
    public const STATUS_DESACTIVE = 0;
    public const STATUS_ACTIVE = 1;

    public const CERTIFICATE_FINGERPRINT_HASH_ALG = 'sha1';

    public const AUTHORITY_ID = 'authority_id';

    private const USER_COLUMNS = 'id, email, login, password, role, authority_id, authority_group_id, status, certificate_hash, name, givenname, cert_not_after';


    public function getPrettyName($name, $givenname, $login)
    {
        return $name ? "$givenname $name" : $login;
    }

    public function getInfo($id): array
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
        if (!User::isExistingRole($role)) {
            throw new UnexpectedValueException("Rôle $role inconnu");
        }
        return User::ROLES_DESCR[$role];
    }

    public function getIdentificationMethod($user_id)
    {
        $info = $this->getInfo($user_id);
        if (!$info) {
            return self::IDENT_METHOD_NONE;
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

    public function getIdsAndPasswordsFromConnexionInfo($certificate_hash, $login)
    {
        $sql = "SELECT id, password FROM users " .
            " WHERE certificate_hash=? " .
                " AND login=? ORDER BY id";

        $data = array($certificate_hash, $login);

        return $this->query($sql, $data);
    }

    public function setPassword(int $userId, string $passwordHash): void
    {
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $data = [$passwordHash,$userId];
        $this->query($sql, $data);
    }

    public function getIdsFromConnexionInfo(string $certificate_hash): array
    {
        $sql = "SELECT id FROM users " .
            " WHERE certificate_hash=? " .
            " ORDER BY id ";

        $data = array($certificate_hash);

        return $this->queryOneCol($sql, $data);
    }

    public function getGroupeName($user_id)
    {
        $sql = "SELECT authority_groups.name FROM users JOIN authority_groups ON users.authority_group_id= authority_groups.id WHERE users.id=?";
        return $this->queryOne($sql, $user_id);
    }

    public function hasDoublon($user_id, $certificat_connexion_info, $login)
    {
        if ($login) {
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

    public function getUserFromCertificatHash(string $certificateHash): array
    {
        return $this->query('SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND status = 1 ORDER BY id', [$certificateHash]);
    }

    public function getUserByCertificatsAndLogin(string $certificateHash, string $login): array
    {
        $userData = $this->queryOne(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND login = ? AND status = 1 ORDER BY id',
            [$certificateHash, $login]
        );

        return $userData === false ? [] : $userData;
    }

    public function getUserByCertificatAndAuthority(string $certificateHash, int $authorityId)
    {
        return $this->queryOne(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND authority_id = ? AND status = 1 ORDER BY id LIMIT 1',
            [$certificateHash, $authorityId]
        );
    }

    public function getUserById(int $id)
    {
        return $this->queryOne(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE id = ? AND status = 1',
            $id
        );
    }

    public function getUserCertificate(int $id): string
    {
        $certificate = $this->queryOne('SELECT certificate FROM users WHERE id = ?', $id);
        if (! $certificate) {
            throw new CertificateException('User certificate not found');
        }

        return $certificate;
    }
}
