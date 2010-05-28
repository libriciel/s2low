<?php
/*
 * T�D�TIS - Copyright 2006 Alternance-Soft
 * Contributeur : J�r�me Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant �  la
 * dématérialisation de l'administration. 
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
 * associés au chargement,  �  l'utilisation,  �  la modification et/ou au
 * développement et �  la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe �  
 * manipuler et qui le réserve donc �  des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités �  charger  et  tester  l'adéquation  du
 * logiciel �  leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * � l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder �  cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \class Group Group.class.php
 * \brief Classe de gestion des groupes de collectivit�s
 * \author J�r�me Schell <j.schell@alternancesoft.com>
 * \date 12.01.2007
 * 
 *
 * Cette classe permet de g�rer les diff�rents groupes de collectivit�s.
 * Les m�thodes de bases sont h�rit�es de DataObject.
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
							  "status" => array( "descr" => "�tat", "type" => "isInt", "mandatory" => true)
							  );


  /**
   * \brief Constructeur d'un groupe de collectivit�
   * \param id integer Num�ro d'id d'un groupe de collectivit� existante avec lequel initialiser l'objet
   */
  public function __construct($id = false) {
    parent::__construct($id);
	if ($id) {
	  $this->init();
	}
  }

  /**
   * \brief M�thode d'initialisation d'un groupe de collectivit� depuis la base de donn�es
   * \return true si succ�s, false sinon
  */
  public function init() {
	return (parent::init() && $this->initSiren());
  }

  /**
   * \brief M�thode qui renvoie la liste des num�ros de SIREN autoris�s pour ce groupe
   */
  public function getAuthorizedSiren() {
	return $this->sirenList;
  }

  /**
   * \brief M�thode permettant de d�terminer si un num�ro de SIREN est autoris� pour le groupe
   * \param $num entier : Num�ro de SIREN � tester
   * \return True si le SIREN est autoris�, False sinon
   */
  public function isAuthorizedSiren($num) {
	if (array_search($num, $this->sirenList) === false) {
	  return false;
	} else {
	  return true;
	}
  }

  /**
   * \brief M�thode d'importation du fichier contenant la liste des SIREN autoris�s pour le groupe
   * \param $file cha�ne : chemin du fichier � importer
   * \return True si succ�s, False sinon
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
		  //formule est: somme (char num�ro impair  +char num�ro paire*2)=(multiple de 10)
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
		  		$this->errorMsg="erreur lors de l'analyse du num�ro SIREN ($content).";
		  		return false; 
		  	}
		  }
		  if (((int)($sum/10))*10 !=$sum)
		  {
		  		$this->errorMsg="le num�ro SIREN ($content) n'est pas correct.";
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
   * \brief M�thode de remise � z�ro des permissions sur les modules
  */
  public function resetSirenList() {
	$this->sirenList = array();
  }

  /**
   * \brief M�thode d'initialisation de la liste des Siren autoris�s pour ce groupe
  */
  public function initSiren() {
	if (isset($this->id)) {
	  $sql = "SELECT siren FROM authority_group_siren WHERE authority_group_id=" . $this->id;

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
   * \brief M�thode permettant de savoir si une collectivit� est active
   * \return true si active, false sinon
  */
  public function isActive() {
    return ($this->status == 1);
  }

  /**
   * \brief M�thode d'enregistrement d'un groupe dans la base de donn�es
   * \param $validate bool�en (optionnel) Pr�cise si la validation de l'entit� doit avoir lieu (true par d�faut)
   * \return true si succ�s, false sinon
   */
  public function save($validate = true) {
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
	  //! Traitement des siren associ�s au groupe
	  $sql = "DELETE FROM authority_group_siren WHERE authority_group_id=" . $this->id;
    
	  if (! $this->db->exec($sql)) {
		$this->errorMsg = "Erreur lors de la r�initialisation des siren associ�s au module.";
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
   * \brief M�thode permettant de supprimer un groupe de la base de donn�es
   * \param $id integer (optionnel) Num�ro d'identifiant du groupe, si non sp�cifi�, groupe en cours
   * \return true si succ�s, false sinon
  */
  public function delete($id = false) {
    // Efface l'entit� sp�cifi�e par $id ou alors l'entit� courante si pas d'id
    if (! $id) {
      if (isset($this->id) && ! empty($this->id)) {
		$id = $this->id;
      } else {
		$this->errorMsg = "Pas d'identifiant pour l'entit� a supprimer";
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
	  $this->errorMsg = "Erreur lors de la suppression des siren autoris�s pour le groupe.";
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
   * \brief M�thode d'obtention de la liste des groupes et tous leurs attributs
   * \param $cond (optionnel) cha�ne Cha�ne contenant les conditions (SQL) � appliquer � la fin de la requ�te BDD
   * \return tableau des groupes
  */
  public function getGroupsList($cond = "") {
	if (! $this->pagerInit('authority_groups.id, authority_groups.name, authority_groups.status', 'authority_groups', $cond)) {
	  return false;
	}

    return $this->data;
  }



  /**********************/
  /* M�thodes statiques */
  /**********************/

  /**
   * \brief M�thode qui d�termine si un groupe comporte des collectivit�s
   * \param $id integer Identifiant du groupe concern�
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
   * \brief M�thode d'obtention d'une liste de groupe
   * \return Tableau de groupe
   *
   * Cette m�thode retourne un tableau dont les clefs sont les identifiants
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
