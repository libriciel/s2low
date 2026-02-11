<?php

//FIXME : cette classe génère une cinquante de requetes inutile ....
//gros potentiel pour accélerer le logiciel (EP)

namespace S2lowLegacy\Class;

//FIXME Les Module n'ont pas à être dans la base de données ....
class Module extends DataObject
{
    //Ajouté par EP afin de ne pas avoir a cherché l'id du module dans la base ....
    public const ACTES = 1;
    public const HELIOS = 2;
    public const MAIL = 3;



    protected $objectName = "modules";
    protected $prettyName = "Module";

    protected $name;
    protected $description;
    protected $menu_entry;
    protected $status;

    protected $dbFields = array( "name" => array( "descr" => "Nom du module", "type" => "isString", "mandatory" => true),
                         "description" => array( "descr" => "Description", "type" => "isString", "mandatory" => true),
                         "menu_entry" => array( "descr" => "Entrée du menu", "type" => "isString", "mandatory" => true),
                         "status" => array( "descr" => "État", "type" => "isInt", "mandatory" => true)
                         );

    private array|null|false $moduleParams = null;

  /**
   * \brief Constructeur d'un module
   * \param id integer : Numéro d'identifiant d'un module existant avec lequel initialiser l'objet
   */
    public function __construct($id = false)
    {
        parent::__construct($id);
    }

  /**
   * \brief Méthode d'initialisation du module d'après son nom
   * \param $name chaîne : Précise le nom du module à initialiser
   * \return true si succès, false sinon
   */
  //FIXME : cette méthode fait deux requete alors qu'une seule est nécessaire
    public function initByName($name)
    {
        $sql = "SELECT id FROM modules WHERE name=?";

        $result = $this->db->select($sql, [$name]);

        if (! $result->isError() && $result->num_row() == 1) {
            $row = $result->get_next_row();
            $this->id = $row["id"];

            return $this->init();
        }

        return false;
    }

  /**
   * \brief Méthode qui détermine si le module courant est actif
   * \return True si le module est actif, false sinon
   */
    public function isActive()
    {
        return (isset($this->status) && $this->status == 1);
    }

  /**
   * \brief Méthode d'enregistrement d'un modules dans la base de données
   * \param $validate booléen (optionnel) Précise si la validation de l'entité doit avoir lieu (true par défaut)
   * \return true si succès, false sinon
   */
    public function save($validate = true, $bouchon_4_strict_standard = true)
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
            $this->errorMsg = "Erreur lors de la sauvegarde du module.";
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
   * \brief Méthode de suppression d'un module dans la base de données
   * \param $id (optionnel) : numéro d'identifiant du module à supprimer
   * \return True en cas de succès, false sinon
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

        $sql = "DELETE FROM modules_authorities WHERE module_id=?";

        if (! $this->db->exec($sql, [$id])) {
            $this->errorMsg = "Erreur lors de la suppression des associations avec les modules.";
            $this->db->rollback();
            return false;
        }

        $sql = "DELETE FROM users_perms WHERE module_id=?";

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
   * \brief Méthode qui permet de fixer le paramètre d'un module
   * \param $name : nom du module
   * \param $description : description du module
   * \param $value : valeur du module
   */
    public function setModuleParams($name, $description, $value)
    {
        $this->moduleParams[] = array("name" => $name,"description" => $description,"value" => $value);
    }

  /**********************/
  /* Méthodes statiques */
  /**********************/

  /**
   * \brief Méthode renvoyant les modules autorisés pour une collectivité
   * \param $authority entier : Numéro d'identifiant de la collectivité
   * \return Un tableau ayant pour clefs les identifiants des modules autorisés
   */
    public static function getModulesForAuthority($authority)
    {

        $sql = "SELECT modules_authorities.id, modules_authorities.module_id " .
        " FROM modules_authorities " .
        " LEFT JOIN modules ON modules_authorities.module_id=modules.id " .
        " WHERE modules_authorities.authority_id= ? AND modules.status=1 " .
        " ORDER BY modules.name";


        $db = DatabasePool::getInstance();

        $result = $db->select($sql, [$authority]);

        $modules = array();

        if (! $result->isError()) {
            while ($row = $result->get_next_row()) {
                $modules[$row["module_id"]] = true;
            }
        }

        return $modules;
    }

  /**
   * \brief Méthode renvoyant les modules autorisés pour un utilisateur (lecture seule ou lecture/écriture)
   * \param $user entier : Numéro d'identifiant de l'utilisateur
   * \return Un tableau ayant pour clefs les identifiants des modules autorisés
   */
    public static function getModulesForUser($user)
    {
        $zeUser = new User($user);
        $zeUser->init();

        $perms = array();

        if ($zeUser->isSuper()) {
            $modules = Module::getActiveModulesList();

            foreach ($modules as $key => $val) {
                $perms[$val["id"]] = array("name" => $val["name"], "description" => $val["description"], "menu_entry" => $val["menu_entry"]);
            }

            return $perms;
        }

        $modules = Module::getModulesForAuthority($zeUser->get("authority_id"));

        $perms = array();

        foreach ($modules as $key => $val) {
            $zeMod = new Module($key);
            $zeMod->init();
            if ($zeUser->canAccess($zeMod->get('name'))) {
                $perms[$key] = array("name" => $zeMod->get("name"),
                            "description" => $zeMod->get("description"),
                            "menu_entry" => $zeMod->get("menu_entry"));
            }
        }

        return $perms;
    }

  /**
   * \brief Méthode d'obtention d'une liste des noms de modules actifs
   * \return tableau des noms de modules
   *
   * Cette méhode retourne un tableau dont les clefs sont les noms
   * des modules et le contenu de la case est le nom du module
  */
    public static function getActiveModulesNames()
    {
        $modules = Module::getActiveModulesList();

        $ret = array();
        foreach ($modules as $module) {
            $ret[$module["name"]] = $module["name"];
        }

        return $ret;
    }

  /**
   * \brief Méthode d'obtention d'une liste de modules actif
   * \return tableau de modules
   *
   * Cette méhode retourne un tableau dont les clefs sont les identifiants
   * des modules et le contenu de la case est le nom du module
  */
    public static function getActiveModulesIdName()
    {
        return Module::getModulesIdName(" WHERE status=1");
    }

  /**
   * \brief Méthode d'obtention d'une liste de modules
   * \param $cond chaîne (optionnel) : condition à appliquer sur la requête SQL
   * \return tableau de modules
   *
   * Cette méhode retourne un tableau dont les clefs sont les identifiants
   * des modules et le contenu de la case est le nom du module
  */
    public static function getModulesIdName($cond = '')
    {
        $modules = Module::getModulesList($cond);

        $tabModules = array();
        foreach ($modules as $key => $module) {
            $tabModules[$module["id"]] = $module["name"];
        }

        return $tabModules;
    }

  /**
   * \brief Méthode qui renvoie la liste des modules actifs
   * \return Un tableau contenant les données des modules actifs
   */
    public static function getActiveModulesList()
    {
        return Module::getModulesList(" WHERE status=1 ORDER BY name ASC");
    }

  /**
   * \brief Méthode qui renvoie la liste des modules
   * \param $cond chaîne (optionnel) : condition à appliquer sur la requête SQL
   * \return Un tableau contenant les données des modules
   */
    public static function getModulesList($cond = "")
    {
        $sql = "SELECT modules.id, modules.name, modules.description, modules.menu_entry, modules.status FROM modules " . $cond;

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        $r = array();
        if (! $result->isError()) {
            $r = $result->get_all_rows();
        }
        foreach ($r as $i => $module) {
            if (in_array($module['name'], array('actes','helios'))) {
                //TODO : revoir les profils avec PK, PEV et EP
                //$specific_perms = array("CS" => "Créer et signer","TT"=>"Télétransmettre");
                $specific_perms = array();
            } else {
                $specific_perms = array();
            }
            $r[$i]['specific_perms'] = $specific_perms;
        }


        return $r;
    }
}
