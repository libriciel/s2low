<?php

namespace S2lowLegacy\Class;

use S2low\Model\AdministeredAuthorities;

class Group extends DataObject
{
    protected $objectName = "authority_groups";
    protected $prettyName = "Groupes";

    protected $name;
    protected $status;

    private $sirenList = array();

    protected $dbFields = array(
                              "name" => array( "descr" => "Nom", "type" => "isString", "mandatory" => true, "unique" => true ),
                              "status" => array( "descr" => "État", "type" => "isInt", "mandatory" => true)
                              );


    /**
     * \brief Constructeur d'un groupe de collectivité
     * \param id integer Numéro d'id d'un groupe de collectivité existante avec lequel initialiser l'objet
     * @param bool $id
     */
    public function __construct($id = false)
    {
        parent::__construct($id);
        if ($id) {
            $this->init();
        }
    }

  /**
   * \brief Méthode d'initialisation d'un groupe de collectivité depuis la base de données
   * \return true si succés, false sinon
  */
    public function init()
    {
        return (parent::init() && $this->initSiren());
    }

    /**
   * \brief Méthode qui renvoie la liste des numéros de SIREN autorisés pour ce groupe
   */
    public function getAuthorizedSiren()
    {
        return $this->sirenList;
    }

  /**
   * Méthode d'initialisation de la liste des Siren autorisés pour ce groupe
  */
    public function initSiren()
    {
        if (isset($this->id)) {
            $sql = "SELECT siren FROM authority_group_siren " .
                " WHERE authority_group_id=?" .
                " ORDER BY siren";

            $result = $this->db->select($sql, [$this->id]);

            if (! $result->isError()) {
                while ($row = $result->get_next_row()) {
                    $this->sirenList[] = $row["siren"];
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

  /**
   * Méthode permettant de savoir si une collectivité est active
   * @return bool true si active, false sinon
  */
    public function isActive()
    {
        return ($this->status == 1);
    }


    /**
     * Méthode permettant de supprimer un groupe de la base de données
     * @param bool|int $id integer (optionnel) Numéro d'identifiant du groupe, si non spécifié, groupe en cours
     * @return bool true si succés, false sinon
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

        if (! $this->isEmpty($id)) {
            $this->errorMsg = "Le groupe n'est pas vide. Suppression impossible.";
            return false;
        }

        if (! $this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

        $sql = "DELETE FROM authority_group_siren WHERE authority_group_id=?";

        if (! $this->db->exec($sql, [$id])) {
            $this->errorMsg = "Erreur lors de la suppression des siren autorisés pour le groupe.";
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
   * Méthode d'obtention de la liste des groupes et tous leurs attributs
   * @param string $cond (optionnel) chaîne Chaîne contenant les conditions (SQL) à appliquer à la fin de la requête BDD
   * @return bool|array tableau des groupes
  */
    public function getGroupsList($cond = "")
    {
        if (! $this->pagerInit('authority_groups.id, authority_groups.name, authority_groups.status', 'authority_groups', $cond, 'authority_groups.name', null, null, 'asc')) {
            return false;
        }

        return $this->data;
    }



  /**********************/
  /* Méthodes statiques */
  /**********************/

  /**
   * Méthode qui détermine si un groupe comporte des collectivités
   * @param int $id Identifiant du groupe concerné
   * @return bool True si le groupe est vide, false sinon
   *
  */
    public static function isEmpty($id)
    {
        $condition = AdministeredAuthorities::conditionForGroup((int)$id);
        $sql = "SELECT authorities.id FROM authorities WHERE $condition";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        if (! $result->isError()) {
            if ($result->num_row() > 0) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

  /**
   * @brief Méthode d'obtention d'une liste de groupe
   * @return bool|array Tableau de groupe
   *
   * Cette méthode retourne un tableau dont les clefs sont les identifiants
   * des groupe et le contenu de la case est le nom du groupe
  */
    public static function getGroupsIdName()
    {
        $sql = "SELECT authority_groups.id, authority_groups.name FROM authority_groups ORDER BY authority_groups.name ASC";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        if (! $result->isError()) {
            $tabGroups = array();

            while ($row = $result->get_next_row()) {
                $tabGroups[$row["id"]] = $row["name"];
            }
        } else {
            return false;
        }
        return $tabGroups;
    }
}
