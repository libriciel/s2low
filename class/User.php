<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

class User extends DataObject
{
    public const PERM_MODIFICATION = "RW";

    public const SADM = 'SADM';
    public const GADM = 'GADM';
    public const ADM = 'ADM';
    public const USER = 'USER';
    public const ARCH = 'ARCH';

    protected $objectName = "users";
    protected $prettyName = "Utilisateur";

    protected $email;
    protected $subject_dn;
    protected $issuer_dn;
    protected $name;
    protected $givenname;
    protected $role;
    protected $telephone;
    protected $authority_group_id;
    protected $authority_id;
    protected $status;
    protected $certificate;
    protected $cert_not_before;
    protected $cert_not_after;
    protected $cert_serial;
    protected $login;
    protected $password;
    protected $certificate_rgs_2_etoiles;
    protected $certificate_hash;

    protected $certFilePath;
    protected $certPassphrase;
    protected $dbFields = array( "email" => array( "descr" => "Adresse électronique", "type" => "isEmail", "mandatory" => true),
                         "subject_dn" => array( "descr" => "Dn du certificat", "type" => "isString", "mandatory" => true),
                         "issuer_dn" => array( "descr" => "DN du fournisseur du certificat", "type" => "isString", "mandatory" => true),
                         "name" => array( "descr" => "Nom", "type" => "isString", "mandatory" => true),
                         "givenname" => array( "descr" => "Prénom", "type" => "isString", "mandatory" => true),
                         "role" => array( "descr" => "Rôle", "type" => "isString", "mandatory" => true),
                         "telephone" => array( "descr" => "Téléphone", "type" => "isString", "mandatory" => false),
                         "authority_group_id" => array( "descr" =>  "Groupe", "type" => "isInt", "mandatory" => false),
                         "authority_id" => array( "descr" =>  "Collectivité", "type" => "isInt", "mandatory" => true),
                         "status" => array( "descr" => "État", "type" => "isInt", "mandatory" => true),
                         "certificate" => array( "descr" => "Certificat de l'utilisateur", "type" => "isString", "mandatory" => false),
                         "cert_not_before" => array( "descr" => "Date d'émission du certificat", "type" => "isDate", "mandatory" => true),
                         "cert_not_after" => array( "descr" => "Date d'expiration du certificat", "type" => "isDate", "mandatory" => true),
                         "cert_serial" => array( "descr" => "Numéro de série du certificat", "type" => "isDate", "mandatory" => true),
                        "login" => array("descr" => "login","type" => "isString","mandatory" => false),
                        "password" => array("descr" => "password","type" => "isString","mandatory" => false),
                        "certificate_rgs_2_etoiles" => array("descr" => "Certificat RGS**","type" => "isString","mandatory" => false),
                        "certificate_hash" => array("descr", "Certificat fingerprint", "type" => "isString", "mandatory" => false)
                         );
    public const ROLES_DESCR = array(
                               self::SADM => 'Super administrateur',
                               self::GADM => 'Administrateur de groupe',
                               self::ADM => 'Administrateur collectivité',
                               self::USER => 'Utilisateur',
                               self::ARCH => 'Archiviste'
                               );
    protected $permsTypes = array(
                                "NONE" => "Aucune",
                                "RO" => "Visualisation",
                                "RW" => "Modification",
                                );
    protected $superPermsTypes = array(
                                     "GRANT" => "Concession"
                                     );
    private $perms;
    private $is_loggued;

  /**
   * \brief Constructeur d'un utilisateur
   * \param $id integer (optionnel) : numéro d'identifiant de l'utilisateur
  */
    public function __construct($id = false)
    {
        parent::__construct($id);
    }

    public function getNbUserWithMyCertificate()
    {
        $sql = "SELECT count(*) AS nb FROM users WHERE certificate_hash=?";

        $result = $this->db->select($sql, [$this->certificate_hash]);
        $row = $result->get_next_row();
        return  $row['nb'];
    }

    /**
     * \brief Méthode d'authentification de l'utilisateur
     * \return true si succès, false sinon
     *
     * Cette méthode vérifie qu'un utilisateur est bien autorisé à se connecter au système.
     * Elle se base sur les données du certificat présenté au serveur Web pour authentifier
     * et initialiser les données de l'utilisateur.
     * @throws \Exception
     */
    public function authenticate(int $authentProcess = Authentification::AUTHENTIFICATION_BY_APACHE)
    {
    /** @var \S2lowLegacy\Class\Authentification $authenfication */
        $authenfication = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier()->get(Authentification::class);
        $this->id = $authenfication->authenticate($authentProcess);

        // Utile si on veut vérifier qui n'est pas en TLSv1.2
        /*
        if (isset($_SERVER['SSL_PROTOCOL']) && $_SERVER['SSL_PROTOCOL'] != 'TLSv1.2' ) {
            file_put_contents(
                "/tmp/openssl_version.log",
                "{$_SERVER['SSL_PROTOCOL']} {$this->id}\n",
                FILE_APPEND
            );
        }
        */

        $this->is_loggued = true;
        $init = $this->init();
        $is_active = $this->isActive();
        return $init && $is_active;
    }

  /**
   * \brief Méthode d'initialisation d'un utilisateur depuis la base de données
   * \return true si succès, false sinon
  */
    public function init()
    {
        return (parent::init() && $this->initPerms());
    }

  /**
   * \brief Méthode d'initialisation des permissions d'un utilisateur depuis la base de données
   * \return true si succès, false sinon
  */
    public function initPerms()
    {
        if (isset($this->id)) {
            $this->resetPerms();

            $authModules = Module::getModulesForAuthority($this->authority_id);

            $sql = "SELECT users_perms.id, users_perms.module_id, users_perms.perm, modules.name " .
            " FROM users_perms LEFT JOIN modules ON users_perms.module_id=modules.id " .
            " WHERE users_perms.user_id=? AND modules.status=1";

            $result = $this->db->select($sql, [$this->id]);

            if (! $result->isError()) {
                while ($row = $result->get_next_row()) {
                  // Ajout de la permission uniquement si la collectivité est autorisée sur ce module
                    if ($this->isGroupAdminOrSuper() || ! empty($authModules[$row["module_id"]])) {
                        $this->perms[$row["name"]] = array("module_id" => $row["module_id"], "perm" => $row["perm"], "id" => $row["id"]);
                    }
                }
            } else {
                $this->errorMsg = "User::initPerms - erreur de récupération des permissions de l'utilisateur";
                return false;
            }
        }

        return true;
    }

  /**
   * \brief Méthode de remise à zéro des permission de l'objet utilisateur courant
  */
    public function resetPerms()
    {
        $this->perms = array();
    }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un administrateur de groupe ou un super administrateur
   * \return true si l'utilisateur est administrateur de groupe ou super administrateur, false sinon
  */
    public function isGroupAdminOrSuper()
    {
        return ($this->isGroupAdmin() || $this->isSuper());
    }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un administrateur de groupe
   * \return true si l'utilisateur est administrateur de groupe, false sinon
  */
    public function isGroupAdmin()
    {
        if (isset($this->role) && ($this->role == "GADM") && is_numeric($this->authority_group_id)) {
            return true;
        } else {
            return false;
        }
    }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un super administrateur
   * \return true si l'utilisateur est super administrateur, false sinon
  */
    public function isSuper()
    {
        if (isset($this->role) && $this->role == 'SADM') {
            return true;
        } else {
            return false;
        }
    }

  /**
   * \brief Méthode qui détermine si l'utilisateur est activé ou non
   * \return true si l'utilisateur est activé, false s'il est désactivé
  */
    public function isActive()
    {
        $authority = new Authority($this->authority_id);

      // Prise en compte du groupe
        if (is_numeric($authority->get("authority_group_id"))) {
            $group = new Group($authority->get("authority_group_id"));
            $groupIsActive = $group->isActive();
        } else {
          // La collectivité n'appartient à aucun groupe
            $groupIsActive = true;
        }

        return ($this->status == 1 && $authority->isActive() && $groupIsActive);
    }

    public function retrieveInfoFromClientCertificate()
    {
         // Ne marche pas avec apache-ssl
        if (! isset($_SERVER['SSL_CLIENT_VERIFY']) || $_SERVER['SSL_CLIENT_VERIFY'] != "SUCCESS") {
            return false;
        }

        if (empty($_SERVER['SSL_CLIENT_CERT'])) {
            return false;
        }

        if (($tab = openssl_x509_parse($_SERVER['SSL_CLIENT_CERT'])) === false) {
            return false;
        }

        // Si l'utilisateur est authentifié par certificat
        $this->issuer_dn = $this->getDn($tab['issuer']);

        // Si l'utilisateur est authentifié par certificat
        $this->subject_dn = $this->getDn($tab['subject']);

        $x509Certificate = new X509Certificate();
        $this->certificate_hash = $x509Certificate->getBase64Hash($_SERVER['SSL_CLIENT_CERT'], UserSQL::CERTIFICATE_FINGERPRINT_HASH_ALG);
    }

    public function logout()
    {
        unset($_SESSION['id_login']);
    }

    public function getCertificateInfo()
    {
        $this->retrieveInfoFromClientCertificate();
        return array('subject' => $this->subject_dn, 'issuer' => $this->issuer_dn,'certificate_hash' => $this->certificate_hash);
    }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un administrateur, un administrateur de groupe ou un super administrateur
   * \return true si l'utilisateur est administrateur, administrateur de groupe ou super administrateur, false sinon
  */
    public function isAdmin()
    {
        if (isset($this->role) && ($this->role == "SADM" || $this->role == "GADM" || $this->role == "ADM")) {
            return true;
        } else {
            return false;
        }
    }

  /**
   * \brief Méthode qui détermine si l'utilisateur est un administrateur de collectivité
   * \return true si l'utilisateur est administrateur de collectivité, false sinon
  */
    public function isAuthorityAdmin(): bool
    {
        return isset($this->role) && $this->role == self::ADM;
    }


    public function isAuthorityAdminFor(int $authority_id)
    {
        return $this->isAuthorityAdmin() && $this->authority_id === $authority_id;
    }

    public function isArchivist(): bool
    {
        return isset($this->role) && $this->role == self::ARCH;
    }

    public function archivistCanAccess(array|bool $transaction_info): bool
    {
        if (!isset($transaction_info['authority_id'])) {
            return false;
        }
        return $this->get('authority_id') === $transaction_info['authority_id'];
    }

    public function isArchivistFor(int $authority_id): bool
    {
        return $this->isArchivist() && $this->authority_id === $authority_id;
    }

    public function canViewAuthority(int $authority_id): bool
    {
        if ($this->isSuper() || $this->authority_id === $authority_id) {
            return true;
        }
        return $this->isGroupAdmin() && (new Authority($authority_id))->isInGroup($this->authority_group_id);
    }

  /**
   * \brief Méthode qui détermine si l'utilisateur courant à les droits pour modifier un autre utilisateur
   * \param $id integer : Numéro d'identifiant de l'utilisateur a éditer
   * \return true si l'utilisateur peut modifier, false sinon
  */
    public function canEditUser($id)
    {
        $sql = "SELECT authority_id FROM users WHERE id=?";

        $result = $this->db->select($sql, [$id]);

        if (! $result->isError() && $result->num_row() == 1) {
            $row = $result->get_next_row();

            $authority = new Authority($row["authority_id"]);

          // Prise en compte du groupe
            if (is_numeric($authority->get("authority_group_id"))) {
                $group = new Group($authority->get("authority_group_id"));
                $inGroup = $this->role == "GADM" && $this->authority_group_id == $group->getId();
            } else {
              // La collectivité n'appartient à aucun groupe
                $inGroup = false;
            }

            return ($this->role == "SADM" || $inGroup || ($row["authority_id"] == $this->authority_id && $this->role == "ADM"));
        } else {
            $this->errorMsg = "User::canEditUser - erreur de résultat requête base de données";
            return false;
        }
    }

  /**
   * \brief Méthode qui détermine si l'utilisateur courant à les droits pour concéder des droits sur un module
   * \param $name chaine : Nom du module
   * \return true si l'utilisateur peut concéder, false sinon
  */
    public function canGrantModule($name)
    {
        if (strcmp($this->getPerm($name), "GRANT") == 0) {
            return true;
        } else {
            return false;
        }
    }

  /**
   * \brief Méthode retournant les permissions de l'utilisateur sur un module particulier
   * \param $module chaîne : nom du module pour lequel récupérer les permissions
   * \return La chaîne des permissions sur le module ou null si aucune permission trouvée
  */
    public function getPerm($module)
    {
        if (empty($this->perms[$module])) {
            return null;
        }
        return $this->perms[$module]["perm"];
    }

  /**
   * \brief Méthode déterminant si un utilisateur peut accéder à une page
   * \param $module chaîne : nom du module
   * \return True si l'utilisateur peut accéder ou false sinon
  */
    public function canAccess($module)
    {
        if ($this->isGroupAdminOrSuper()) {
            return true;
        }
        $authority = new Authority($this->authority_id);

        $mod = new Module();
        if (! $mod->initByName($module)) {
            return false;
        }

        if (! $authority->getModulePerm($mod->getId())) {
            return false;
        }

        if ($this->getPerm($module) != "NONE" && $this->getPerm($module) != null) {
            return true;
        }
        return false;
    }

    public function checkDroit($module, $droit)
    {
        if ($this->isGroupAdminOrSuper()) {
            return true;
        }
        $authority = new Authority($this->authority_id);

        $mod = new Module();
        if (! $mod->initByName($module)) {
            return false;
        }

        if (! $authority->getModulePerm($mod->getId())) {
            return false;
        }

        if ($this->getPerm($module) == "RW") {
            return true;
        }

        if ($this->getPerm($module) == $droit) {
            return true;
        }
        return false;
    }

  /**
   * \brief Méthode déterminant si un utilisateur à accès en modification
   * \param $module chaîne : nom du module
   * \return True si l'utilisateur peut modifier ou false sinon
  */
    public function canEdit($module)
    {
        if ($this->isGroupAdminOrSuper()) {
            return true;
        }
        $authority = new Authority($this->authority_id);

        $mod = new Module();
        if (! $mod->initByName($module)) {
            return false;
        }

        if (! $authority->getModulePerm($mod->getId())) {
            return false;
        }
        if ($this->getPerm($module) == "RW") {
            return true;
        }
        return false;
    }

  /**
   * \brief Méthode permettant de fixer les permissions d'un utilisateur sur un module
   * \param $module_id integer : numéro d'identifiant du module dont fixer les permissions
   * \param $perm chaîne : permission sur le module
   * \return true si succès, false sinon
  */
    public function setPerm($module_id, $perm, array $specific_perms = array())
    {
        $module = new Module($module_id);
        if (! $module->init()) {
            return false;
        }

        $all_perms = $this->getPermTypes($specific_perms);

        if (empty($all_perms[$perm])) {
            $perm = "NONE";
        }

        $this->perms[$module->get("name")] = array("module_id" => $module->getId(), "perm" => $perm);
        return true;
    }

    public function getPermTypes(array $module_specific_perms = array())
    {
        $result = $this->permsTypes;

        if ($this->isSuper()) {
            $result = array_merge($result, $this->superPermsTypes);
        }

        $result = array_merge($result, $module_specific_perms);

        return $result;
    }

  /**
   * \brief Méthode renvoyant le nom d'un utilisateur formatté "Prénom Nom"
   * \return La chaîne du nom de l'utilisateur
  */
    public function getPrettyName()
    {
        if (mb_strlen($this->name) > 0) {
            $str = $this->givenname .  " " . $this->name;
        } else {
            $str = $this->login;
        }

        return $str;
    }

    /**
     * \brief Méthode d'enregistrement d'un utilisateur dans la base de données
     * \param $validate booléen (optionnel) Demande la validation ou non des données de l'entité avant enregistrement (défaut : true)
     * \return true si succès, false sinon
     * @throws \Exception
     */
    public function save($validate = true, $bouchon_4_strict_standard = true)
    {
        if (isset($this->certFilePath)) {
            if (! $this->importCert()) {
                return false;
            }
        }

        $saveSQLRequest = parent::buildSaveSQLRequest($validate);
        if (! $saveSQLRequest->isValid()) {
            return false;
        }

        $new = !isset($this->id);

        if (! $this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

        if (! $this->db->exec($saveSQLRequest->getRequest(), $saveSQLRequest->getParams())) {
            $this->errorMsg = "Erreur lors de la sauvegarde de l'utilisateur.";
            $this->db->rollback();
            return false;
        }

      // Traitement permissions sur les modules
        $sql = "DELETE FROM users_perms WHERE user_id=" . $this->id;

        if (! $this->db->exec($sql)) {
            $this->errorMsg = "Erreur lors de la réinitialisation des permissions de l'utilisateur.";
            $this->db->rollback();
            return false;
        }

        if (isset($this->perms)) {
            reset($this->perms);
            foreach ($this->perms as $perm) {
                $sql = "INSERT INTO users_perms (module_id, user_id, perm) VALUES(?, ?, ?)";

                if (! $this->db->exec($sql, [$perm["module_id"], $this->id, $perm["perm"]])) {
                      $this->errorMsg = "Erreur lors de la sauvegarde des permissions de l'utilisateur.";
                      $this->db->rollback();
                      return false;
                }
            }
        }

        // Reset login / Password
        if (!$new && empty($this->login) && empty($this->password)) {
            $sql = "UPDATE users SET login ='', password='' WHERE id = " . $this->id;
            if (! $this->db->exec($sql)) {
                $this->errorMsg = "Erreur lors du reset du login/mot de passe.";
                $this->db->rollback();
                return false;
            }
        }

        if (! $this->db->commit()) {
            $this->errorMsg = "Erreur lors de la validation de la transaction.";
            $this->db->rollback();
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode d'import des informations contenus dans le certificat utilisateur
   * \return true si succès, false sinon
  */
    private function importCert()
    {
        if (isset($this->certFilePath)) {
            if (! $this->certificate = file_get_contents($this->certFilePath)) {
                $this->errorMsg = "Erreur de traitement du certificat.";
                return false;
            }

            if (($tab = openssl_x509_parse($this->certificate)) === false) {
                $this->errorMsg = "Erreur d'analyse du certificat.";
                return false;
            }

            $this->issuer_dn = "";
            foreach ($tab['issuer'] as $key => $val) {
                $this->issuer_dn .= "/" . $key . "=" . $val;
            }

            $this->subject_dn = $tab["name"];

          /*
            Il semble qu'il y a un bug avec l'utilisation des validFrom et validTo qui retourne
          une date au format GeneralizedTime ou UTCTime. Les champs validFrom_time_t et
          validTo_time_t semble plus *stable*.
          */
            $this->cert_not_before = date("Y-m-d H:i:s", $tab['validFrom_time_t']);
            $this->cert_not_after = date("Y-m-d H:i:s", $tab['validTo_time_t']);


            $this->cert_serial = $tab["serialNumber"];


            $x509 = new X509Certificate();
            $this->certificate_hash = $x509->getBase64Hash($this->certificate, UserSQL::CERTIFICATE_FINGERPRINT_HASH_ALG);


          // Controle de l'existence d'un utilisateur avec les mêmes données de certificat.
          // Si un utilisateur a les mêmes données mais qu'il s'agit de l'utilisateur courant
          // on accepte => permet de modifier le certificat
            $ids = $this->getIdFromCertData($this->certificate_hash);

            if ((! empty($ids) && $this->isNew()) || (! empty($ids) && ! $this->isNew() && $ids[0] != $this->id)) {
                if ($ids[0]) {
                    $autre = new User($ids[0]);
                    $autre->init();
                    if (! $autre->get('login')) {
                        $this->errorMsg = "Un utilisateur avec les mêmes données de certificat existe déjà. Vous pouvez mettre un login/mot de passe pour les différencier";
                        return false;
                    }
                }

                if ($this->login) {
                    $id = $this->getIdFromLogin($this->login);
                    if ($id) {
                        $this->errorMsg = "Un utilisateur avec le même login existe déjà.";
                        return false;
                    }

                    return true;
                }
                $this->errorMsg = "Un utilisateur avec les mêmes données de certificat existe déjà. Vous pouvez mettre un login/mot de passe pour les différencier";
                return false;
            }

            return true;
        }

        $this->errorMsg = "Pas de certificat fournit.";
        return false;
    }

    public function getIdFromCertData($certificate_hash)
    {
        $resultat = array();
        $sql = "SELECT id FROM users WHERE certificate_hash=?" .
            " ORDER BY name,givenname,login";

        $result = $this->db->select($sql, [$certificate_hash]);

        if ($result->isError() || $result->num_row() == 0) {
            $this->errorMsg = "User::getIdFromCertData - Échec du mappage de l'utilisateur depuis les informations du certificat";
            return false;
        }

        while ($row = $result->get_next_row()) {
            $resultat[] = $row['id'];
        }
        return $resultat;
    }

    public function getIdFromLogin($login)
    {
        $sql = "SELECT id FROM users WHERE users.login=? AND certificate_hash=?";

        $result = $this->db->select($sql, [$login,$this->certificate]);
        if ($result->num_row() == 0) {
            return false;
        }
        $row = $result->get_next_row();
        return $row['id'];
    }

  /**
   * \brief Méthode de suppression d'un utilisateur de la base de données
   * \param $id integer (optionnel) Numéro d'identifiant de l'utilisateur, si non spécifié, entité en cours
   * \return true si succès, false sinon
  */
    public function delete($id = false)
    {
      // Efface l'entité spécifiée par $id ou alors l'entité courante si pas d'id
        if (! $id) {
            if (isset($this->id) && ! empty($this->id)) {
                $id = $this->id;
            } else {
                $this->errorMsg = "Pas d'identifiant pour l'entité a supprimer";
                return false;
            }
        }

        if (! $this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

        $sql = "DELETE FROM users_perms WHERE user_id=" . $id;

        if (! $this->db->exec($sql)) {
            $this->errorMsg = "Erreur lors de la suppression des permissions de l'utilisateur.";
            $this->db->rollback();
            return false;
        }

        if (! parent::delete($id)) {
            $this->db->rollback();
            return false;
        }

        if (! $this->db->commit()) {
            $this->errorMsg = "Erreur lors de la validation de la transaction.";
            $this->db->rollback();
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode de récupération de la liste des utilisateurs
   * \param $cond chaîne (optionnel) : condition à appliquer sur la requête SQL
   * \return Un tableau contenant les données des utilisateurs
  */
    public function getUsersList($cond = "")
    {
        if (! $this->pagerInit('users.id, users.name, users.givenname, users.email, users.role, users.authority_group_id, users.telephone, users.status, users.authority_id, authorities.name AS authority_name, users.login, users.cert_not_after ', 'users LEFT OUTER JOIN authorities ON users.authority_id=authorities.id', $cond, 'users.name', null, null, "ASC")) {
            return false;
        }

        return $this->data;
    }

    public function getUserSiren()
    {
        $sql = "SELECT siren FROM users, authorities WHERE users.authority_id=authorities.id AND users.id=" . $this->id;
        $db = DatabasePool::getInstance();


        $result = $this->db->select($sql);

        if (! $result->isError() && $result->num_row() == 1) {
            $row = $result->get_next_row();
            return $row["siren"];
        } else {
            $this->errorMsg = "User::getUserSiren - Échec du mappage de l'utilisateur depuis les informations du certificat";
            return false;
        }
    }

    public function cloneCertificat($new_id)
    {
        $clone = new User($new_id);
        $clone->init();
        foreach (array('subject_dn', 'issuer_dn', 'certificate', 'cert_not_before', 'cert_not_after', 'cert_serial', 'certificate_hash') as $info) {
            $this->set($info, $clone->get($info));
        }
    }

    public static function isExistingRole(string $val): bool
    {
        return array_key_exists($val, self::ROLES_DESCR);
    }

  /**
   * \brief Méthode permettant de fixer la valeur d'un attribut
   * \param $name chaîne : Nom de l'attribut
   * \param $val : valeur de l'attribut
  */
    public function set($name, $val): void
    {
        switch ($name) {
            case 'role':
                if (!$this->isExistingRole($val)) {
                    $val = self::USER;
                }
                break;
        }

        parent::set($name, $val);
    }

    public function getAllPossibleAuthority()
    {
        assert(!!$this->id);
        if ($this->isSuper()) {
            $sql = "SELECT id,name FROM authorities ORDER by name";
        } elseif ($this->isGroupAdmin()) {
            $sql = "SELECT id,name FROM authorities WHERE authority_group_id=" . $this->get('authority_group_id') . " ORDER by name";
        } else {
            return array($this->get("authority_id") => '');
        }
        $result = array();
        foreach ($this->db->fetchAll($sql) as $r) {
            $result[$r['id']] = $r['name'];
        }
        return $result;
    }

    private function getCertificateExpirationTime()
    {
        $x509Certificate = new X509Certificate();
        return strtotime($x509Certificate->getExpirationDate($this->get('certificate')));
    }

    public function getCertificateExpirationDate()
    {
        return date("d/m/Y H:i:s", $this->getCertificateExpirationTime());
    }

    public function getNbDaysBeforeCertificatExpire()
    {
        return floor(($this->getCertificateExpirationTime() - time()) / 86400);
    }

    /**
     * @param $issuer
     * @return string
     */
    public function getDn($issuer): string
    {
        $dn = "";
        foreach ($issuer as $key => $val) {
            if (is_string($val)) {
                $dn .= "/" . $key . "=" . $val;
            }
            if (is_array($val)) {
                foreach ($val as $value) {
                    $dn .= "/" . $key . "=" . $value;
                }
            }
        }
        return $dn;
    }

    public function getAvailableRolesForUserCreation(): array
    {
        if ($this->isSuper()) {
            return self::ROLES_DESCR;
        }
        // Les admin simple et de groupe ne peut pas créer un super admin, un admin de groupe ou un archiviste
        return array_intersect_key(
            self::ROLES_DESCR,
            array_flip([self::USER,self::ADM])
        );
    }
}
