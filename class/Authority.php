<?php

/*
 *
 * Cette classe permet de gérer les différentes collectivités.
 * Les méthodes de bases sont héritées de DataObject.
*/

namespace S2lowLegacy\Class;

class Authority extends DataObject
{
    protected $objectName = "authorities";
    protected $prettyName = "Collectivité";

    protected $name;
    protected $siren;
    protected $authority_group_id;
    protected $agreement;
    protected $email;
    protected $broadcast_email;
    protected $default_broadcast_email;
    protected $status;
    protected $authority_type_id;
    protected $address;
    protected $postal_code;
    protected $city;
    protected $department;
    protected $district;
    protected $telephone;
    protected $fax;
    protected $ext_siret;
    protected $helios_ftp_login;
    protected $helios_ftp_password;
    protected $helios_ftp_dest;
    protected $email_mail_securise;
    protected $descr_mail_securise;
    protected $new_notification;
    protected $sae_wsdl;
    protected $helios_do_not_verify_nom_fic_unicity;
    protected $helios_use_passtrans;

    private $modulesPerms = null;

    protected $dbFields = array( "name" => array( "descr" => "Nom", "type" => "isString", "mandatory" => true),
                         "siren" => array( "descr" => "Numéro de SIREN", "type" => "isString", "mandatory" => true, "unique" => true),
                         "authority_group_id" => array( "descr" => "Groupe de collectivité", "type" => "isInt", "mandatory" => true),
                         "agreement" => array( "descr" => "Référence convention", "type" => "isString", "mandatory" => false),
                         "email" => array( "descr" => "Adresse électronique", "type" => "isEmail", "mandatory" => false),
                         "broadcast_email" => array( "descr" => "Adresse électronique de diffusion", "type" => "isEmail", "mandatory" => false),
                         "default_broadcast_email" => array( "descr" => "Adresse électronique de diffusion par défaut", "type" => "isEmail", "mandatory" => false),
                         "status" => array( "descr" => "État", "type" => "isInt", "mandatory" => true),
                         "authority_type_id" => array( "descr" => "Type de collectivité", "type" => "isString", "mandatory" => true),
                         "address" => array( "descr" => "Adresse", "type" => "isString", "mandatory" => true),
                         "postal_code" => array( "descr" => "Code postal", "type" => "isString", "mandatory" => true),
                         "city" => array( "descr" => "Ville", "type" => "isString", "mandatory" => true),
                         "department" => array( "descr" => "Département", "type" => "isString", "mandatory" => true),
                         "district" => array( "descr" => "Arrondissement", "type" => "isString", "mandatory" => true),
                         "telephone" => array( "descr" => "Téléphone", "type" => "isString", "mandatory" => false),
                         "fax" => array( "descr" => "Fax", "type" => "isString", "mandatory" => false),
                        "ext_siret" => array("descr" => "ext_SIRET","type" => "isString", "mandatory" => false),
                        "helios_ftp_login" => array("descr" => "helios_ftp_login","type" => "isString","mandatory" => false),
                        "helios_ftp_password" => array("descr" => "helios_ftp_password", "type" => "isString", "mandatory" => false),
                        "helios_ftp_dest" => array("descr" => "helios_ftp_dest", "type" => "isString", "mandatory" => false),
                        "email_mail_securise" => array("descr" => "Email pour le module de mail sécurisé", "type" => "isEmail", "mandatory" => false),
                        "descr_mail_securise" => array("descr" => "Description pour le module de mail sécurisé", "type" => "isString", "mandatory" => false),
                        "new_notification" => array("descr" => "Nouveau systeme de notification", "type" => "boolean", "mandatory" => false),
                        "sae_wsdl" => array("descr" => '','type' => 'isString','mandatory' => false,'unique' => false),
                        "helios_do_not_verify_nom_fic_unicity" => array("descr" => '','type' => 'isBool','mandatory' => false,'unique' => false),
                        "helios_use_passtrans" => array("descr" => 'helios_use_passtrans','type' => 'isBool','mandatory' => false,'unique' => false)
                         );


  /**
   * \brief Constructeur d'une collectivité
   * \param id integer Numéro d'id d'une collectivité existante avec lequel initialiser l'objet
   */
    public function __construct($id = false)
    {
        parent::__construct($id);
        if ($id) {
            $this->init();
            $this->initModulesPerms();
        }
    }

  /**
   * \brief Méthode d'initialisation d'une collectivité depuis la base de données
   * \return true si succès, false sinon
  */
    public function init()
    {
        return (parent::init() && $this->initModulesPerms());
    }

  /**
   * \brief Méthode d'obtention du département et de l'arrondissement d'appartenance de la collectivité
   * \return Une chaîne indiquant le département et l'arrondissement
   */
    public function getDeptDistrString()
    {
        if (! empty($this->department) && ! empty($this->district)) {
            $str = "";

            $sql = "SELECT id, name FROM authority_departments WHERE code=?";

            $result = $this->db->select($sql, [$this->department]);

            if (! $result->isError() && $result->num_row() == 1) {
                $row = $result->get_next_row();
                $str .= $row["name"];
                $deptId = $row["id"];
            } else {
                $str .= $this->department;
                $deptId = null;
            }

            $str .= "&nbsp;/&nbsp;";

            $sql = "SELECT name FROM authority_districts WHERE code=?";
            $sqlParams = [$this->district];
            if ($deptId) {
                $sql .= " AND authority_department_id=?";
                $sqlParams[] = $deptId;
            }

            $result = $this->db->select($sql, $sqlParams);

            if (! $result->isError() && $result->num_row() == 1) {
                $row = $result->get_next_row();
                $str .= $row["name"];
            } else {
                $str .= $this->district;
            }

            return $str;
        }

        return null;
    }

  /**
   * \brief Méthode qui renvoie la liste des modules autorisés pour cette collectivité
   */
    public function getAuthorizedModules()
    {
        if (! $this->modulesPerms) {
            $this->initModulesPerms();
        }

        return $this->modulesPerms;
    }

  /**
   * \brief Méthode qui détermine si la collectivité appartient au groupe spécifié
   * \param $group_id integer : Numéro d'identifiant du groupe
   * \return True si la collectivité appartient au groupe, False sinon
  */
    public function isInGroup($group_id)
    {
        if (is_numeric($group_id)) {
            if ($this->authority_group_id == $group_id) {
                return true;
            }
        }

        return false;
    }

  /**
   * \brief Méthode d'initialisation des permissions de la collectivité sur les modules
  */
    public function initModulesPerms()
    {
        if (isset($this->id)) {
            $moduleSQL = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
            $this->modulesPerms = $moduleSQL->getModulesForAuthority($this->id);
            return true;
        }

        return false;
    }

  /**
   * \brief Méthode de remise à zéro des permissions sur les modules
  */
    public function resetModulesPerms()
    {
        $this->modulesPerms = null;
    }

  /**
   * \brief Méthode qui permet de fixer les permissions d'une collectivité sur un module
   * \param $module_id integer Numéro identifiant le module sur lequel fixer la permission
   * \param $val booléen ou chaine (optionnel) Valeur de la permission, true par défaut, peut être "1" ou "on" ou true pour activer la permission
  */
    public function setModulePerm($module_id, $val = true)
    {
        if ($val == "1" || $val == "on" || $val === true) {
            $this->modulesPerms[$module_id] = true;
        }
    }

  /**
   * \brief Méthode d'obtention de la permission d'une collectivité sur un module
   * \param $module_id integer Numéro d'identifiant du module
   * \return true si autorisée, false sinon
  */
    public function getModulePerm($module_id)
    {
        if (! $this->modulesPerms) {
            $this->getAuthorizedModules();
        }

        if (isset($this->modulesPerms[$module_id])) {
            return $this->modulesPerms[$module_id];
        } else {
            return false;
        }
    }

    public function getModulePermByName($module_name)
    {
        $sql = "SELECT id FROM modules WHERE name=?";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql, [$module_name]);
        if (! $result->isError()) {
            $row = $result->get_next_row();
            $id = $row["id"];
            return $this->getModulePerm($id);
        } else {
            return false;
        }
    }
  /**
   * \brief Méthode permettant de savoir si une collectivité est active
   * \return true si active, false sinon
  */
    public function isActive()
    {
        return ($this->status == 1);
    }

    /**
     * \brief Méthode d'enregistrement d'une collectivité dans la base de données
     * \param $module_perms (optionnel) : si true (défaut) sauvegarde aussi les permissions sur les modules
     * \param $validate booléen (optionnel) Précise si la validation de l'entité doit avoir lieu (true par défaut)
     * \return true si succès, false sinon
     * @throws \Exception
     */
    public function save($module_perms = true, $validate = true)
    {
        $saveSQLRequest = parent::buildSaveSQLRequest($validate);
        if (! $saveSQLRequest->isValid()) {
            return false;
        }

      //echo $sql;
      //exit();

        if (! $this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

        if (! $this->db->exec($saveSQLRequest->getRequest(), $saveSQLRequest->getParams())) {
            $this->errorMsg = "Erreur lors de la sauvegarde de la collectivité.";
            $this->db->rollback();
            return false;
        }

        $existingTransaction = isset($this->id);

        if ($existingTransaction && empty($this->helios_ftp_dest)) {
            $sql = "UPDATE authorities SET helios_ftp_dest ='' WHERE id = " . $this->id;
            if (! $this->db->exec($sql)) {
                $this->errorMsg = "Erreur lors du reset du helios_ftp_dest.";
                $this->db->rollback();
                return false;
            }
        }

        if ($existingTransaction && empty($this->email)) {
            $sql = "UPDATE authorities SET email ='' WHERE id = " . $this->id;
            if (! $this->db->exec($sql)) {
                $this->errorMsg = "Erreur lors du reset du email.";
                $this->db->rollback();
                return false;
            }
        }

        if ($existingTransaction && empty($this->broadcast_email)) {
            $sql = "UPDATE authorities SET broadcast_email ='' WHERE id = " . $this->id;
            if (! $this->db->exec($sql)) {
                $this->errorMsg = "Erreur lors du reset du broadcast_email.";
                $this->db->rollback();
                return false;
            }
        }

        if ($existingTransaction && empty($this->default_broadcast_email)) {
            $sql = "UPDATE authorities SET default_broadcast_email ='' WHERE id = " . $this->id;
            if (! $this->db->exec($sql)) {
                $this->errorMsg = "Erreur lors du reset du default_broadcast_email.";
                $this->db->rollback();
                return false;
            }
        }

        if ($module_perms) {
          // Traitement permissions sur les modules
            $sql = "DELETE FROM modules_authorities WHERE authority_id=?";

            if (! $this->db->exec($sql, [$this->id])) {
                $this->errorMsg = "Erreur lors de la réinitialisation des permissions de la collectivité.";
                $this->db->rollback();
                return false;
            }

            if (!is_null($this->modulesPerms) && count($this->modulesPerms) > 0) {
                reset($this->modulesPerms);
                foreach ($this->modulesPerms as $module_id => $val) {
                    $sql = "INSERT INTO modules_authorities (module_id, authority_id) VALUES(?,?)";

                    if (! $this->db->exec($sql, [$module_id,$this->id])) {
                        $this->errorMsg = "Erreur lors de la sauvegarde des permissions de la collectivité.";
                        $this->db->rollback();
                        return false;
                    }
                }
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
   * \brief Méthode permettant de supprimer une collectivité de a base de données
   * \param $id integer (optionnel) Numéro d'identifiant de la collectivité, si non spécifié, collectivité en cours
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

        $sql = "DELETE FROM helios_retour WHERE authority_id=?";

        if (! $this->db->exec($sql, [$id])) {
            $this->errorMsg = "Erreur lors de la suppression des PES Retour.";
            $this->db->rollback();
            return false;
        }

        $sql = "DELETE FROM authority_siret WHERE authority_id=?";

        if (! $this->db->exec($sql, [$id])) {
            $this->errorMsg = "Erreur lors de la suppression des SIRET.";
            $this->db->rollback();
            return false;
        }

        $sql = "DELETE FROM logs WHERE authority_id=?";

        if (! $this->db->exec($sql, [$id])) {
            $this->errorMsg = "Erreur lors de la suppression des logs.";
            $this->db->rollback();
            return false;
        }

        $sql = "DELETE FROM modules_authorities WHERE authority_id=?";

        if (! $this->db->exec($sql, [$id])) {
            $this->errorMsg = "Erreur lors de la suppression des associations avec les modules.";
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
   * \brief Méthode d'obtention de la liste des collectivités et tous leurs attributs
   * \param $cond (optionnel) chaîne Chaîne contenant les conditions (SQL) à appliquer à la fin de la requête BDD
   * \return tableau des collectivités
  */
    public function getAuthoritiesList($cond = "", $count = null)
    {
        if (
            ! $this->pagerInit(
                'authorities.id, authorities.name, authorities.siren, authorities.authority_group_id, authorities.agreement, authorities.email, authorities.broadcast_email, authorities.authority_type_id, authority_types.description AS type_name, authorities.address, authorities.postal_code, authorities.city, authorities.telephone, authorities.fax',
                'authorities LEFT JOIN authority_types ON authorities.authority_type_id=authority_types.id',
                $cond,
                'authorities.name',
                $count,
                null,
                "ASC"
            )
        ) {
            return false;
        }

        return $this->data;
    }


    public function getAllAuthorities()
    {
        return $this->db->fetchAll("SELECT id,name,siren FROM authorities ORDER BY id");
    }


  /**********************/
  /* Méthodes statiques */
  /**********************/

  /**
   * \brief Méthode d'obtention d'une liste de collectivité
   * \param $cond chaine : condition SQL a appliquer sur la requete
   * \return Tableau de collectivités
   *
   * Cette méthode retourne un tableau dont les clefs sont les identifiants
   * des collectivités et le contenu de la case est le nom de la collectivité
  */
    public static function getAuthoritiesIdName($cond = "")
    {
        $sql = "SELECT authorities.id, authorities.name FROM authorities " . $cond;

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        $authorities = false;
        if (! $result->isError()) {
            $authorities = $result->get_all_rows();
        }

        $tabAuthorities = array();
        if (count($authorities) > 0) {
            foreach ($authorities as $key => $authority) {
                $tabAuthorities[$authority["id"]] = $authority["name"];
            }
        }

        return $tabAuthorities;
    }

    public static function getSirenFromId($id)
    {
        $sql = "SELECT siren FROM authorities WHERE id = ?";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql, [$id]);

        if (!$result->isError() && $result->num_row() == 1) {
            $row = $result->get_next_row();
            return $row["siren"];
        }

        return false;
    }


  /**
   * \brief Méthode d'obtention de la liste id/name des types de collectivités
   * \return Tableau des types de collectivités
   *
   * Cette méthode retourne un tableau dont les clefs sont les identifiants
   * des types et le contenu de la case est le nom du type (tronqué à 40 caractères)
   *
  */
    public static function getAuthorityTypesIdName()
    {
      // Obtention des types parents
        $sql = "SELECT id, parent_type_id, description FROM authority_types where parent_type_id is not null";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        $types = array();

        if (! $result->isError()) {
            while ($row = $result->get_next_row()) {
                $types[$row["id"]] = $row["id"] . ' - ' . mb_substr($row["description"], 0, 40);

                if (mb_strlen($row["description"]) > 40) {
                    $types[$row["id"]] .= "...";
                }
            }
        }

        return $types;
    }

  /**
   * \brief Méthode d'obtention de la liste des types de collectivités
   * \return Tableau des types de collectivités
  */
    public static function getAuthorityTypes()
    {
      // Obtention des types parents
        $sql = "SELECT id, parent_type_id, description FROM authority_types WHERE parent_type_id is null";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        $types = array();

        if (! $result->isError()) {
            while ($row = $result->get_next_row()) {
                $types[] = array("code" => $row["id"], "description" => $row["description"], "type" => "parent");

                $sql = "SELECT id, parent_type_id, description FROM authority_types WHERE parent_type_id=? ORDER BY id";

                $result2 = $db->select($sql, [$row["id"]]);
                if (! $result2->isError()) {
                    while ($row2 = $result2->get_next_row()) {
                        $types[] = array("code" => $row2["id"], "description" => $row2["description"], "type" => "child");
                    }
                }
            }
        }

        return $types;
    }

  /**
   * \brief Méthode d'obtention d'une liste id/nom de départements
   * \return Tableau de départements
   *
   * Cette méthode retourne un tableau dont les clefs sont les identifiants
   * des départements et le contenu de la case est le nom du département
  */
    public static function getDepartmentsIdName()
    {
        $sql = "SELECT code, name FROM authority_departments";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        $departments = array();

        if (! $result->isError()) {
            while ($row = $result->get_next_row()) {
                $departments[$row["code"]] = $row["name"];
            }
        }

        return $departments;
    }

  /**
   * \brief Méthode d'obtention d'une liste de département
   * \return Tableau de départements
   *
  */
    public static function getDepartmentsList()
    {
        $sql = "SELECT id, code, name FROM authority_departments ORDER BY code";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);


        if (! $result->isError()) {
            return $result->get_all_rows();
        }

        return array();
    }


  /**
   * \brief Méthode d'obtention d'une liste d'arrondissements pour un département donné
   * \return Tableau d'arrondissements
   *
   * Cette méthode retourne un tableau dont les clefs sont les identifiants
   * des arrondissements et le contenu de la case est le nom de l'arrondissement
  */
    public static function getDistrictsForDepartment($dept)
    {
        $districts = array();

        if (isset($dept) && ! empty($dept)) {
            $sql = "SELECT authority_districts.code, authority_districts.name FROM authority_districts LEFT JOIN authority_departments ON authority_districts.authority_department_id=authority_departments.id WHERE authority_departments.code=?";

            $db = DatabasePool::getInstance();

            $result = $db->select($sql, [$dept]);

            if (! $result->isError()) {
                while ($row = $result->get_next_row()) {
                    $districts[$row["code"]] = $row["name"];
                }
            }
        }

        return $districts;
    }
}
