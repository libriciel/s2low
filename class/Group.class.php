<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématèrialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \class Group Group.class.php
 * \brief Classe de gestion des groupes de collectivités
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 12.01.2007
 * 
 *
 * Cette classe permet de gérer les différents groupes de collectivités.
 * Les méthodes de bases sont héritées de DataObject.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

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
   * \brief Méthode qui renvoie la liste des numéros de SIREN autorisés pour ce groupe
   */
  public function getAuthorizedSiren() {
	return $this->sirenList;
  }

  /**
   * \brief Méthode permettant de déterminer si un numéro de SIREN est autorisé pour le groupe
   * \param $num entier : Numéro de SIREN à tester
   * \return True si le SIREN est autorisé, False sinon
   */
  public function isAuthorizedSiren($num) {
	if (array_search($num, $this->sirenList) === false) {
	  return false;
	} else {
	  return true;
	}
  }

  /**
   * \brief Méthode d'importation du fichier contenant la liste des SIREN autorisés pour le groupe
   * \param $file chaîne : chemin du fichier à importer
   * \return True si succés, False sinon
   */
  public function importSiren($file) {
	$this->resetSirenList();

	if (! $handle = fopen($file, "r")) {
	  $this->errorMsg = "Erreur lors de l'ouverture du fichier.";
	  return false;
	}

	while ($content = fgets($handle)) {
	  $content = trim($content);

	  //suppose qu'on a n char,le 1 char est char[0]
	  if (VERIFICATION_SIREN)
	  {
		  //formule est: somme (char numéro impair  +char numéro paire*2)=(multiple de 10)
		  $j=1;
		  $sum=0;
		  for ($i=0;$i<strlen($content);$i++)
		  {
		  	$c=substr($content,$i,1);
		  	$num=(int) $c;
		  	if ($j==1)
		  	{
		  		$sum=$sum+$num;
		  		$j=0;
		  	}
		  	else if($j==0)
		  	{
		  		$sum=$sum+$num*2;
		  		$j=1;
		  	}
		  	else
		  	{
		  		$this->errorMsg="erreur lors de l'analyse du numéro SIREN ($content).";
		  		return false; 
		  	}
		  }
		  if (((int)($sum/10))*10 !=$sum)
		  {
		  		$this->errorMsg="le numéro SIREN ($content) n'est pas correct.";
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
   * \brief Méthode de remise à zéro des permissions sur les modules
  */
  public function resetSirenList() {
	$this->sirenList = array();
  }

  /**
   * \brief Méthode d'initialisation de la liste des Siren autorisés pour ce groupe
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
   * \brief Méthode permettant de savoir si une collectivité est active
   * \return true si active, false sinon
  */
  public function isActive() {
    return ($this->status == 1);
  }

  /**
   * \brief Méthode d'enregistrement d'un groupe dans la base de données
   * \param $validate booléen (optionnel) Précise si la validation de l'entité doit avoir lieu (true par défaut)
   * \return true si succés, false sinon
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
   * \brief Méthode permettant de supprimer un groupe de la base de données
   * \param $id integer (optionnel) Numéro d'identifiant du groupe, si non spécifié, groupe en cours
   * \return true si succés, false sinon
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
   * \brief Méthode d'obtention de la liste des groupes et tous leurs attributs
   * \param $cond (optionnel) chaîne Chaîne contenant les conditions (SQL) à appliquer à la fin de la requête BDD
   * \return tableau des groupes
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
   * \brief Méthode qui détermine si un groupe comporte des collectivités
   * \param $id integer Identifiant du groupe concerné
   * \return True si le groupe est vide, false sinon
   *
  */
  public static function isEmpty($id) {
    $sql = "SELECT authorities.id FROM authorities WHERE authority_group_id=" . $id;

    $db =& DatabasePool::getInstance();

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
   * \brief Méthode d'obtention d'une liste de groupe
   * \return Tableau de groupe
   *
   * Cette méthode retourne un tableau dont les clefs sont les identifiants
   * des groupe et le contenu de la case est le nom du groupe
  */
  public static function getGroupsIdName() {
    $sql = "SELECT authority_groups.id, authority_groups.name FROM authority_groups ORDER BY authority_groups.name ASC";

    $db =& DatabasePool::getInstance();

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
  /**
   * @brief obtenir la list de group
   * @return String array liste du group
   */
  public static function getGroupList()
  {
	  $sql = "SELECT , authority_districts.name FROM authority_districts LEFT JOIN authority_departments ON authority_districts.authority_department_id=authority_departments.id WHERE authority_departments.code='" . $dept . "'";

	  $db =& DatabasePool::getInstance();

	  $result = $db->select($sql);

	  if (! $result->isError()) {
		while ($row = $result->get_next_row()) {
		  $districts[$row["code"]] = $row["name"];
		}
	  }
  }
}
?>
