<?php

require_once("DataObject.class.php");
require_once("Module.class.php");

class Group extends DataObject {

  protected $objectName = "authority_groups";
  protected $prettyName = "Groupes";

  protected $name;
  protected $status;

  private $recordSiren = false;
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
  public function __construct($id = false) {
    parent::__construct($id);
	if ($id) {
	  $this->init();
	}
  }

  /**
   * \brief Méthode d'initialisation d'un groupe de collectivité depuis la base de données
   * \return true si succés, false sinon
  */
  public function init() {
	return (parent::init() && $this->initSiren());
  }

	/**
	 * Méthode permettant de déterminer si un numéro de SIREN est autorisé pour le groupe
	 * @param integer $num Numéro de SIREN à tester
	 * @return bool True si le SIREN est autorisé, False sinon
	 */
	public function isAuthorizedSiren($num) {
		if (array_search($num, $this->sirenList) === false) {
			return false;
		} else {
			return true;
		}
	}

	/**
   * \brief Méthode qui renvoie la liste des numéros de SIREN autorisés pour ce groupe
   */
  public function getAuthorizedSiren() {
	return $this->sirenList;
  }

  /**
   * Méthode d'importation du fichier contenant la liste des SIREN autorisés pour le groupe
   * @param $file string chemin du fichier à importer
   * @return bool True si succés, False sinon
   */
  public function importSiren($file) {
	$this->resetSirenList();

	if (! $handle = fopen($file, "r")) {
	  $this->errorMsg = "Erreur lors de l'ouverture du fichier.";
	  return false;
	}

        $theSiren  = new Siren(new LuhnKey());
	while ($content = fgets($handle)) {
	  $content = trim($content);

	  if (VERIFICATION_SIREN)
	  {		
              if (! $theSiren->isValid($content)) {
                  $this->errorMsg="erreur lors de l'analyse du numéro SIREN ($content).";
                  return false;
              }  
	  }
	  if (! empty($content) && strlen($content) <= 9) {
		$this->sirenList[] = $content;
	  }
	}

	$this->recordSiren = true;

	return true;
  }


  /**
   * Méthode de remise à zéro des permissions sur les modules
  */
  public function resetSirenList() {
	$this->sirenList = array();
  }

  /**
   * Méthode d'initialisation de la liste des Siren autorisés pour ce groupe
  */
  public function initSiren() {
	if (isset($this->id)) {
	  $sql = "SELECT siren FROM authority_group_siren " .
	  			" WHERE authority_group_id={$this->id}" .
	  			" ORDER BY siren";

	  $result = $this->db->select($sql);

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
  public function isActive() {
    return ($this->status == 1);
  }

  /**
   * Méthode d'enregistrement d'un groupe dans la base de données
   * @param $validate bool (optionnel) Précise si la validation de l'entité doit avoir lieu (true par défaut)
   * @param $bouchon_4_strict_standard bool permet d'éviter un warning sur les standard stricts, mais ne sert à rien
   * @return bool true si succés, false sinon
   */
  public function save($validate = true,$bouchon_4_strict_standard = true) {
    if (! ($sql = parent::save($validate, true))) {
	  return false;
	}
  	 
  	//echo $sql;
    //exit();
    if (! $this->db->begin()) {
      $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
      return false;
	}

    if (! $this->db->exec($sql)) {
      $this->errorMsg = "Erreur lors de la sauvegarde du groupe.";
	  $this->db->rollback();
      return false;
    }

	if ($this->recordSiren) {    
	  //! Traitement des siren associés au groupe
	  $sql = "DELETE FROM authority_group_siren WHERE authority_group_id=" . $this->id;
    
	  if (! $this->db->exec($sql)) {
		$this->errorMsg = "Erreur lors de la réinitialisation des siren associés au module.";
		$this->db->rollback();
		return false;
	  }

	  if (count($this->sirenList) > 0) {
		reset($this->sirenList);
		foreach ($this->sirenList as $siren) {
		  $sql = "INSERT INTO authority_group_siren (authority_group_id, siren) VALUES(" . $this->id . ", '" . addslashes($siren) . "')";
		  if (! $this->db->exec($sql)) {
			$this->errorMsg = "Erreur lors de la sauvegarde des siren du groupe.";
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
	 * Méthode permettant de supprimer un groupe de la base de données
	 * @param bool|int $id integer (optionnel) Numéro d'identifiant du groupe, si non spécifié, groupe en cours
	 * @return bool true si succés, false sinon
	 */
  public function delete($id = false) {
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

	$sql = "DELETE FROM authority_group_siren WHERE authority_group_id=" . $id;

    if (! $this->db->exec($sql)) {
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
  public function getGroupsList($cond = "") {
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
  public static function isEmpty($id) {
    $sql = "SELECT authorities.id FROM authorities WHERE authority_group_id=" . $id;

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
  public static function getGroupsIdName() {
    $sql = "SELECT authority_groups.id, authority_groups.name FROM authority_groups ORDER BY authority_groups.name ASC";

    $db =DatabasePool::getInstance();

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
