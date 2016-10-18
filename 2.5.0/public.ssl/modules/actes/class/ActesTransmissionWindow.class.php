<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
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
 * \class ActesTransmissionWindow ActesTransmissionWindow.class.php
 * \brief Cette classe permet de gérer les fenêtres de transmission ACTES
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 23.08.2006
 * 
 *
 * Cette classe fournit des méthodes de gestion des fenêtres
 * de transmission ACTES.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once(SITEROOT . "/class/DataObject.class.php");

class ActesTransmissionWindow extends DataObject {
  protected $objectName = "actes_transmission_windows";
  
  protected $rate_limit;

  protected $dbFields = array( "rate_limit" => array( "descr" => "Limitation de débit", "type" => "isInt", "mandatory" => true)
							   );

  protected $window_start_date;
  protected $window_start_stamp;
  protected $window_end_date;
  protected $window_end_stamp;

  private $windowHours;

  /**
   * \brief Constructeur d'une entrée de fenêtre de transmission
   * \param id integer Numéro d'identifiant d'une entrée existante avec laquelle initialiser l'objet
   */
  public function __construct($id = false) {
	parent::__construct($id);
  }

  /**
   * \brief Méthode initialisant l'entité avec l'identifiant courant
   * \return true si succès, false sinon
  */
  public function init() {
	if (parent::init()) {
	  $sql = "SELECT MIN(window_begin) AS min, MAX(window_end) AS max FROM actes_transmission_window_hours WHERE transmission_window_id = " . $this->id;

	  $result = $this->db->select($sql);

	  if (! $result->isError()) {
		$row = $result->get_next_row();
		$this->window_start_date = $row["min"];
		$this->window_end_date = $row["max"];
	  } else {
		return false;
	  }
	} else {
	  return false;
	}

	return true;
  }

  /**
   * \brief Méthode d'enregistrement d'une entité dans la base de données
   * \param $validate booléen (optionnel) Demande la validation ou non des données de l'entité avant enregistrement (true par défaut)
   * \return true si succès, false sinon
   */
  public function save($validate = true) {
	$new = false;
	if ($this->isNew()) {
	  $new = true;
	}

    if (! ($sql = parent::save($validate, true))) {
	  return false;
	}

	if (! $new) {
	  $hours = $this->computeHours($this->getWindowHours());
	} else {
	  $hours = $this->computeHours();
	}

	if ($hours === false) {
	  $this->errorMsg = "Erreur de récupération des heures de la fenêtre.";
	  return false;
	} elseif (count($hours) <= 0) {
	  $this->errorMsg = "La fenêtre a une durée nulle.";
	  return false;
	}

	if (! $this->db->begin()) {
      $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
      return false;
	}

    if (! $this->db->exec($sql)) {
      $this->errorMsg = "Erreur lors de la sauvegarde de la fenêtre.";
	  $this->db->rollback();
      return false;
    }
	// Suppression des heures existante
	$sql = "DELETE FROM actes_transmission_window_hours WHERE transmission_window_id=" . $this->id;
    if (! $this->db->exec($sql)) {
      $this->errorMsg = "Erreur lors de la réinitialisation des heures de la fenêtre.";
	  $this->db->rollback();
      return false;
    }

	// Insertion des heures de la fenêtre
	foreach ($hours as $hour) {
	  $sql = "INSERT INTO actes_transmission_window_hours (transmission_window_id, window_begin, window_end, consumed) VALUES (" . $this->id . ", '"
		. $hour["window_begin"] . "', '" . $hour["window_end"] . "', " . $hour["consumed"] . ")";

	  if (! $this->db->exec($sql)) {
		$this->errorMsg = "Erreur lors de l'insertion des heures de la fenêtre.";
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
   * \brief Méthode de suppression d'une fenêtre de la base de données
   * \param $id integer (optionnel) Numéro d'identifiant de la fenêtre, si non spécifié, entité en cours
   * \return true si succès, false sinon
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

	if (! $this->db->begin()) {
      $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
      return false;
	}

	$sql = "DELETE FROM actes_transmission_window_hours WHERE transmission_window_id=" . $id;

    if (! $this->db->exec($sql)) {
	  $this->errorMsg = "Erreur lors de la suppression des heures associées à la fenêtre.";
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
   * \brief Méthode de calcul des heures de la fenêtre courante
   * \param $currentHours tableau (optionnel) : Tableau contenant les heures actuelles de la fenêtre en cas de modification
   * \return Un tableau des heures calculées
   */
  private function computeHours($currentHours = false) {
	// Nombre d'heures nécessaires
	$nb_hours = ceil(($this->window_end_stamp - $this->window_start_stamp) / 3600);

	$newHours = array();

	for ($i = 0; $i < $nb_hours; $i++) {
	  $start_stamp = $this->window_start_stamp + $i * 3600;
	  $consumed = 0;

	  if ($currentHours) {
		foreach ($currentHours as $hour) {
		  // Récupération des octets déjà consommés
		  if (Helpers::getTimestampFromBDDDate($hour["window_begin"]) == $start_stamp) {
			if (! empty($hour["consumed"])) {
			  $consumed = $hour["consumed"];
			}
		  }
		}
	  }

	  $start_date = date('Y-m-d H:i:s', $start_stamp);
	  $end_date = date('Y-m-d H:i:s', $start_stamp + 3599);

	  $newHours[] = array("window_begin" => $start_date, "window_end" => $end_date, "consumed" => $consumed);
	}

	return $newHours;
  }

  /**
   * \brief Méthode qui calcule si la fenêtre courante est en collision avec une fenêtre existante
   * \return Un tableau d'identifiant des fenêtres qui interfèrent ou false s'il n'y a pas de collision
   */
  public function hasCollision() {
	$hoursId = array();
	if (isset($this->window_start_stamp) && isset($this->window_end_stamp)) {
	  $start_date = date('Y-m-d H:i:s', $this->window_start_stamp);
	  $end_date = date('Y-m-d H:i:s', ($this->window_end_stamp - 1));

	  $sql = "SELECT DISTINCT transmission_window_id FROM actes_transmission_window_hours"
		. " WHERE ((window_begin <= '" . $start_date . "' AND window_end >= '" . $start_date . "')"
		. " OR (window_begin <= '" . $end_date . "' AND window_end >= '" . $end_date . "')"
		// Notre intervalle englobe totalement un autre intervalle
		. " OR (window_begin >= '" . $start_date . "' AND window_end <= '" . $end_date . "'))";

	  // Ne pas tenir compte des chevauchements avec nous-même
	  if (! $this->isNew()) {
		$sql .= " AND transmission_window_id != " . $this->id;
	  }

	  //echo $sql;
	  //exit();

	  $result = $this->db->select($sql);

	  if (! $result->isError() && $result->num_row() > 0) {
		while ($row = $result->get_next_row()) {
		  $hoursId[] = $row["transmission_window_id"];
		}
	  } else {
		return false;
	  }
	}

	return $hoursId;
  }

  /**
   * \brief Méthode de récupération des heures correspondant à un fenêtre
   * \return Un tableau contenant les données des heures en cas de succès, false sinon
   */
  private function getWindowHours() {
	if (isset($this->id)) {
	  $sql = "SELECT id, window_begin, window_end, consumed FROM actes_transmission_window_hours WHERE transmission_window_id=" . $this->id;

	  $result = $this->db->select($sql);

	  $windowHours = array();

	  if (! $result->isError()) {
		while ($row = $result->get_next_row()) {
		  $windowHours[] = $row;
		}
	  } else {
		return false;
	  }

	  return $windowHours;
	} else {
	  return false;
	}

	return false;
  }

  /**
   * \brief Méthode de récupération de la liste des fenêtres de transmission
   * \param $cond chaîne (optionnel) : condition à appliquer sur la requête SQL
   * \return Un tableau contenant les données des fenêtres
  */
  public function getWindowsList($cond = "") {
	if (! $this->pagerInit('actes_transmission_windows.id, actes_transmission_windows.rate_limit, MIN(atwh.window_begin) AS start, MAX(atwh.window_end) AS end', 'actes_transmission_windows LEFT OUTER JOIN actes_transmission_window_hours atwh ON actes_transmission_windows.id=atwh.transmission_window_id', $cond . " GROUP BY actes_transmission_windows.id, actes_transmission_windows.rate_limit")) {
	  return false;
	}

    return $this->data;
  }

  /**
   * \brief Méthode de détermination de l'heure pleine la plus proche de l'heure passée en paramètre
   * \param $date chaîne : La date au format YYYY-MM-DD
   * \param $hour chaîne : L'heure au format HH:MM:SS
   * \return Un timestamp correspondant à l'heure pleine
  */
  public static function roundDate($date, $hour) {
	$timestamp = Helpers::ansiDateToTimestamp($date, true);

	$hours = explode(':', $hour);

	if (count($hours) == 3) {
	  $timestamp += $hours[0] * 60 * 60;

	  if ($hours[1] > 30) {
		$timestamp += 3600;
	  }
	}

	return $timestamp;
  }

}