<?php

/**
 * \class DataObject DataObject.class.php
 * \brief Classe de base pour la gestion d'entité en base de données
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 17.02.2006
 *
 *
 * Cette classe fournit des méthodes de base pour la gestion d'entité
 * stockées en base de données (initialisation, sauvegarde, suppression...)
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

namespace S2lowLegacy\Class;

class DataObject
{
    protected $id;
    protected $errorMsg = null;

    /**
     * @var Database
     */
    protected $db;

    protected $statusTypes = array( 0 => "Désactivé",
                            1 => "Activé"
                            );

  // Données de pagination
    protected $displayItems;
    protected $currentPage;
    protected $pageNbr;
    protected $fields;
    protected $from;
    protected $cond;
    protected $order;

    protected $totalRecords;

    public $data;

    protected $dbFields;
    protected $objectName;


  /**
   * \brief Constructeur
   * \param id integer (optionnel) Numéro d'id d'une entité existante avec lequel initialiser l'objet
   */
    public function __construct($id = false)
    {
        $this->db = DatabasePool::getInstance();

        if ($id) {
            $this->id = $id;
        }
    }

  /**
   * \brief Méthode renvoyant le numéro d'identifiant de l'entité en cours
   * \return l'id en cours ou null si non définit
  */
    public function getId()
    {
        return (isset($this->id)) ? $this->id : null;
    }

  /**
   * \brief Méthode permettant de fixer l'identifiant de l'entité en cours
   * \param $pId integer Numéro d'identifiant de l'entité
  */
    public function setId($pId)
    {
        $this->id = $pId;
    }

  /**
   * \brief Méthode permettant de fixer la valeur d'un attribut
   * \param $name chaîne : Nom de l'attribut
   * \param $val : valeur de l'attribut
  */
    public function set($name, $val)
    {
        $this->$name = $val;
    }

  /**
   * \brief Méthode permettant de récupérer la valeur d'un attribut
   * \param $name chaîne : Nom de l'attribut
   * \return Valeur de l'attribut ou null si l'attribut n'existe pas
  */
    public function get($name)
    {
        return (isset($this->$name)) ? $this->$name : null;
    }

  /**
   * \brief Méthode permettant de déterminer si l'entité est un nouvel enregistrement ou non
    * \return true si nouvel enregistrement, false sinon
  */
    public function isNew()
    {
        if (! isset($this->id) || empty($this->id)) {
            return true;
        } else {
            return false;
        }
    }

  /**
   * \brief Méthode initialisant l'entité avec l'identifiant courant
   * \return true si succès, false sinon
  */
    public function init()
    {

        if (isset($this->id) && ! empty($this->id)) {
            $sql = "SELECT " . implode(", ", array_keys($this->dbFields)) . " FROM " . $this->objectName . " WHERE id=?";
            $result = $this->db->select($sql, [$this->id]);

            if (! $result->isError() && $result->num_row() == 1) {
                $row = $result->get_next_row();

                foreach (array_keys($this->dbFields) as $key) {
                    $this->$key = \S2lowLegacy\Class\Helpers\StringHelper::getFromBDD($row[$key]);
                }

                return true;
            } else {
                $this->errorMsg = "::init - Résultat incorrect pour l'initialisation de l'entité";
                return false;
            }
        }

        return false;
    }

  /**
   * \brief Méthode de suppression d'une entité dans la base de données
   * \param $id integer (optionnel) Numéro d'identifiant de l'identité, si non spécifié, entité en cours
   * \return true si succès, false sinon
  */
    public function delete($id = false)
    {
      // Efface l'entité spécifiée par $id ou alors l'entité courante si pas d'id
        if (! $id) {
            if (isset($this->id) && is_numeric($this->id)) {
                $id = $this->id;
            } else {
                $this->errorMsg = "Pas d'identifiant pour l'entité a supprimer";
                return false;
            }
        }

        $sql = "DELETE FROM " . $this->objectName . " WHERE id= ? ";

        if (! $this->db->exec($sql, [$id])) {
            //Never reached...
            $this->errorMsg = "Erreur lors de la suppression de l'entité d'identifiant " . $id;
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode permettant de valider les attributs de l'entité courante (bon type, présence...)
   * \return true si succès, false sinon
  */
    public function validate()
    {
        $this->errorMsg = "";

        foreach ($this->dbFields as $name => $attr) {
            if (! empty($attr["mandatory"]) && (! isset($this->$name) || mb_strlen($this->$name) <= 0)) {
                $this->errorMsg .= $attr["descr"] . " doit être présent.\n";
            } else {
                if (isset($this->$name) && ! empty($this->$name)) {
                    if (! empty($attr["unique"]) && ! $this->checkUnicity($name)) {
                        $this->errorMsg .= $attr["descr"] . " doit être unique.\n";
                    } else {
                        switch ($attr["type"]) {
                            case "isInt":
                                if (! preg_match("/^[-+]?[0-9]+$/", $this->$name)) {
                                    $this->errorMsg .= $attr["descr"] . " doit être un entier.\n";
                                }
                                break;
                            case "isFloat":
                                if (! preg_match("/^[-+]?[0-9]+(\.[0-9]+)*$/", $this->$name)) {
                                    $this->errorMsg .= $attr["descr"] . " doit être un réel.\n";
                                }
                                break;
                            case "isEmail":
                                if (! is_valid_email($this->$name)) {
                                    $this->errorMsg .= $attr["descr"] . " doit être une adresse électronique valide.\n";
                                }
                                break;
                        }

                        if (isset($attr["maxlength"])) {
                            if (mb_strlen($this->$name) > $attr["maxlength"]) {
                                $this->errorMsg .= "Le champ " . $attr["descr"] . " est trop long (" . $attr["maxlength"] . " caractères maxi autorisés).\n";
                            }
                        }

                        if (isset($attr["regexp"])) {
                            if (! preg_match($attr["regexp"], $this->$name)) {
                                $this->errorMsg .= "Le champ " . $attr["descr"] . " " . $attr["regexp_txt"];
                            }
                        }
                    }
                }
            }
        }

        if (! empty($this->errorMsg)) {
            return false;
        } else {
            return true;
        }
    }

  /**
   * \brief Méthode permettant de vérifier qu'un attribut est unique dans une table
   * \param $name chaîne : Nom de l'attribut
   * \return true si unique, false sinon
  */
    public function checkUnicity($name)
    {
        $sql = "SELECT * FROM " . $this->objectName . " WHERE " . $name . "=?";
        $params = [$this->$name];

        if (isset($this->id)) {
            $sql .= " AND id != ?";
            $params[] = $this->id;
        }

        $result = $this->db->select($sql, $params);

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
   * \brief Méthode d'enregistrement d'une entité dans la base de données
   * \param $validate booléen (optionnel) Demande la validation ou non des données de l'entité avant enregistrement (true par défaut)
   * \return true si succès, false sinon
  */
    public function save($validate = true)
    {

        $saveSQLRequest = $this->buildSaveSQLRequest($validate);

        if (!$saveSQLRequest->isValid()) {
            return false;
        }

        if (! $this->db->exec($saveSQLRequest->getRequest(), $saveSQLRequest->getParams())) {
            $this->errorMsg = "Erreur lors de la sauvegarde de l'entité";
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode de récupération du prochain identifiant dans la base de données pour cette entité
   * \return l'identifiant ou null si échec
  */
    protected function getNextId()
    {
        $sql = "SELECT nextval('" . $this->objectName . "_id_seq') AS id";

        $result = $this->db->select($sql);

        if (! $result->isError()) {
            $row = $result->get_next_row();

            return $row["id"];
        }

        return null;
    }

  /**
   * \brief Méthode d'obtention de la chaine à passer en paramètre à la fonction de validation javascript des formulaires
   * \param .. Un nombre variable de chaînes représentant les noms des variables membres à valider
   * \return La chaîne de description des champs à valider
  */
    public function getValidationTrio()
    {
        $args = func_get_args();

        $ret = array();

        for ($i = 0; $i < count($args); $i++) {
            if (isset($this->dbFields[$args[$i]])) {
                $str = "'" . addslashes($args[$i]) . "', '" . addslashes($this->dbFields[$args[$i]]["descr"]) . "', '";

                if ($this->dbFields[$args[$i]]["mandatory"]) {
                    $str .= "R";
                }

                $str .= addslashes($this->dbFields[$args[$i]]["type"]);

                if (! empty($this->dbFields[$args[$i]]["maxlength"])) {
                    $str .= "maxLength" . $this->dbFields[$args[$i]]["maxlength"] . "!";
                }

                if (! empty($this->dbFields[$args[$i]]["regexp"])) {
                  // On enlève les caractères / au début et à la fin de la regexp, javascript les rajoute automatiquement
                    $str .= "RegExp" . mb_substr($this->dbFields[$args[$i]]["regexp"], 1, -1) . "#";
                }

                $str .= "'";

                $ret[] = $str;
            }
        }

        return implode(',', $ret);
    }

  // Méthodes de pagination


  /**
   * \brief Méthode d'initialisation du pager
   * \param $fields chaîne : Liste des champs à récupérer
   * \param $from chaîne : Liste des tables dans lesquelles récupérer les données (peut inclure des jointures)
   * \param $cond chaîne (optionnel) : Chaîne conditionnelle (comporte une instruction WHERE)
   * \param $order chaîne (optionnel) : Chaîne définissant l'ordre de tri pour les résultats (SQL)
   * \param $count entier (optionnel) : Nombre de résultat par page désirés
   * \param $page entier (optionnel) : Page courante désirée
   * \return True en cas de succès, false sinon
  */
    public function pagerInit($fields, $from, $cond = null, $order = null, $count = null, $page = null, $sortWay = "DESC")
    {
        $this->fields = $fields;
        $this->from = $from;
        $this->cond = $cond;

        $this->order = $this->objectName . ".id";


        if ($order) {
            $this->order = $order;
        } elseif (isset($_GET["order"])) {
          // On vérifie que ce champ est bien présent dans la table concernée
            if (array_search($_GET["order"], array_keys($this->dbFields)) !== false) {
                $this->order = $this->objectName . "." . $_GET["order"];
            }
            if (isset($_GET["sortway"])) {
                if ($_GET["sortway"] == "asc") {
                    $sortWay = " ASC";
                }
            }
        }

        $this->order .= " " . $sortWay;

        if ($count) {
            $this->displayItems = $count;
        } elseif (isset($_GET["count"]) && is_numeric($_GET["count"])) {
            $this->displayItems = $_GET["count"];
        } else {
            $this->displayItems = DEFAULT_ITEMS_PER_PAGE;
        }

        if ($page) {
            $this->currentPage = $page;
        } elseif (isset($_GET["page"]) && is_numeric($_GET["page"])) {
            $this->currentPage = $_GET["page"];
        } else {
            $this->currentPage = 1;
        }

        if (! $this->pagerCountRecords()) {
            $this->errorMsg = "Erreur lors du comptage des enregistrements.";
            return false;
        } else {
            if (! $this->pagerFetchData()) {
                $this->errorMsg = "Erreur lors de la récupération des enregistrements.";
                return false;
            }
        }

        return true;
    }

  /**
   * \brief Méthode de comptage du nombre total d'enregistrements pour le pager
   * \return True en cas de succès, false sinon
  */
    protected function pagerCountRecords()
    {
      // Comptage du nombre total d'enregistrements
        $sql = "SELECT count(*) AS total_count FROM " . $this->from . " " . $this->cond;

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        if (! $result->isError()) {
            $row = $result->get_next_row();

            // Nombre total d'enregistrements
            $this->totalRecords = $row["total_count"] ?? 0; //Quickfix passage PHP 8

            // Nombre de pages en fonction du nombre d'items par page
            $this->pageNbr = ceil($this->totalRecords / $this->displayItems);

            // Controle du débordement des pages
            if ($this->currentPage > $this->pageNbr) {
                 $this->currentPage = $this->pageNbr;
            }
        } else {
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode de récupération des données pour le pager depuis la base de données
   * \return True en cas de succès, false sinon
  */
    protected function pagerFetchData()
    {
        $offset = ($this->currentPage - 1) * $this->displayItems;
        if ($offset < 0) {
            $offset = 0;
        }
        $sql = "SELECT " . $this->fields . " FROM " . $this->from . " " . $this->cond;

        if (! empty($this->order)) {
            $sql .= " ORDER BY " . $this->order;
        }

        $sql .= " LIMIT " . $this->displayItems . " OFFSET " . $offset;

      //echo $sql;

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        if (! $result->isError()) {
            $this->data = $result->get_all_rows();
        } else {
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode de récupération du message d'erreur associé à l'entité
   * \return Le message d'erreur
  */
    public function getErrorMsg()
    {
        $msg = $this->errorMsg;
        $this->errorMsg = null;

        return $msg;
    }

    /**
     * @param mixed $validate
     * @return DataObjectSaveSQLRequest
     */
    public function buildSaveSQLRequest(bool $validate): DataObjectSaveSQLRequest
    {
        $new = true;
        $error = true;

        if (isset($this->id)) {
            $new = false;
        }

        if ($validate) {
            if (!$this->validate()) {
                return new DataObjectSaveSQLRequest(false, "", []);
            }
        }

        if ($new) {
            if (!($this->id = $this->getNextId())) {
                $this->errorMsg = "Erreur de récupération du nouvel ID";
                return new DataObjectSaveSQLRequest(false, "", []);
            }


            $fields = ["id"];
            $params = [$this->id];
            $values = "? ";

            foreach ($this->dbFields as $field => $val) {
                if (isset($this->$field) && mb_strlen($this->$field) > 0) {
                    $values .= ", ?";
                    $fields[] = $field;
                    $params[] = $this->$field;
                }
            }

            $sql = "INSERT INTO " . $this->objectName . " (";
            $sql .= implode(", ", $fields);
            $sql .= ") VALUES ( $values )";
        } else { // Mise à jour
            $sql = "UPDATE " . $this->objectName . " SET ";

            $fields = array();
            $params = array();
            foreach ($this->dbFields as $field => $val) {
                if (isset($this->$field) && mb_strlen($this->$field) > 0) {
                    $str = $field . "= ?";
                    $params[] = $this->$field;
                    $fields[] = $str;
                }
            }

            $sql .= implode(", ", $fields);

            $sql .= " WHERE id=?";
            $params[] = $this->id;
        }
        return new DataObjectSaveSQLRequest(true, $sql, $params);
    }
}
