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
/**
 * \class ActesBatchFile ActesBatchFile.class.php
 * \brief Cette classe permet de gérer les fichiers contenus dans les lots de transactions ACTES
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 08.02.2007
 * 
 *
 * Cette classe fournit des méthodes de gestion des fichiers contenus dans les lots de transactions
 * Actes
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once(SITEROOT . "/class/DataObject.class.php");

class ActesBatchFile extends DataObject {
  protected $objectName = "actes_batch_files";
  
  protected $batch_id;
  protected $transaction_id;
  protected $filename;
  protected $filesize;
  protected $signature;
  protected $status;

  protected $storage_dir;
  protected $tmpFile;
  protected $tmpName;

  protected $dbFields = array(
							  "batch_id" => array( "descr" => "Lot contenant ce fichier", "type" => "isInt", "mandatory" => true),
							  "transaction_id" => array( "descr" => "Transaction issu du fichier", "type" => "isInt", "mandatory" => false),
							  "filename" => array( "descr" => "Nom du fichier sur le serveur", "type" => "isString", "mandatory" => true),
							  "filesize" => array( "descr" => "Taille du fichier", "type" => "isInt", "mandatory" => true),
							  "signature" => array( "descr" => "Signature électronique du fichier", "type" => "isString", "mandatory" => false),
							  "status" => array( "descr" => "Statut du fichier", "type" => "isString", "mandatory" => true)
							  );

  private $fileStatus = array(
							  "UNPRO" => "Non traité",
							  "PRO" => "Traité"
							  );

  /**
   * \brief Constructeur d'un fichier de lot
   * \param id integer Numéro d'identifiant d'un fichier de lot existant avec lequel initialiser l'objet
   */
  public function __construct($id = false) {
	parent::__construct($id);

	if (isset($this->id)) {
	  if (! $this->init()) {
		unset($this->id);
	  }
	}
  }

  /**
   * \brief Détermination si un fichier est déjà traité ou pas
   * \return True si le fichier est traité, false sinon
   */
  public function isProcessed() {
	if (strcmp($this->status, 'PRO') == 0) {
	  return true;
	} else {
	  return false;
	}
  }

  /**
   * \brief Positionnement du statut sur "Non traité"
   */
  public function setUnprocessed() {
	$this->status = 'UNPRO';
  }

  /**
   * \brief Positionnement du statut sur "Traité"
   */
  public function setProcessed() {
	$this->status = 'PRO';
  }

  /**
   * \brief Méthode d'obtention du chemin absolu vers le fichier
   * \return Chemin du fichier sous forme de chaîne
   */
  public function getAbsoluteFilePath() {
	if (! empty($this->filename)) {
	  return ACTES_BATCHES_UPLOAD_ROOT . "/" . $this->filename;
	} else {
	  return null;
	}
  }

  /**
   * \brief Méthode d'obtention du nom de base du fichier
   * \return Nom de base du fichier
   */
  public function getDisplayName() {
	return basename($this->filename);
  }

  /**
   * \brief Mise en place du fichier dans le répertoire de stockage du lot
   * \return True en cas de succès, false sinon
   */
  public function putTempFileInStorageDir() {
	if (! move_uploaded_file($this->tmpFile, $this->getAbsoluteFilePath())) {
	  return false;
	}

	return true;
  }

  /**
   * \brief Suppression physique du fichier du lot
   * \return True en cas de succès, false sinon
   */
  public function deleteFile() {
	return Helpers::deleteFromFS(ACTES_BATCHES_UPLOAD_ROOT . "/" . $this->filename);
  }

  /**
   * \brief Méthode d'enregistrement d'un lot dans la base de données
   * \param $validate booléen (optionnel) Demande la validation ou non des données de l'entité avant enregistrement (true par défaut)
   * \return true si succès, false sinon
   */
  public function save($validate = true) {
	$new = false;
	if ($this->isNew()) {
	  $new = true;

	  if (empty($this->storage_dir) || empty($this->tmpFile) || empty($this->tmpName)) {
		$this->errorMsg = "Informations manquantes pour la création du fichier de lot.";
		return false;
	  }

	  $this->filename = $this->storage_dir . "/" . $this->tmpName;

	  if (! $this->putTempFileInStorageDir()) {
		$this->errorMsg = "Erreur lors de la mise en place du fichier du lot.";
		return false;
	  }

	  if (! $this->filesize = @filesize($this->getAbsoluteFilePath())) {
		$this->errorMsg = "Erreur détermination taille fichier.";
		return false;
	  }
	}

    if (! (parent::save($validate))) {
	  return false;
	}

	return true;
  }

  /**
   * \brief Méthode de suppression d'un fichier de lot
   * \return true si succès, false sinon
  */
  public function delete() {
	if (! $this->deleteFile()) {
	  return false;
	}

	if (! parent::delete()) {
	  return false;
	}

	return true;
  }

  /**
   * \brief Méthode qui renvoie le fichier au navigateur
   * \return True en cas de succès, false sinon
   */
  public function sendFile() {
	$ret_value = false;

	if (isset($this->filename)) {
	  if (file_exists($this->getAbsoluteFilePath())) {
		if (! Helpers::sendFileToBrowser($this->getAbsoluteFilePath(), basename($this->getAbsoluteFilePath()), @mime_content_type($this->getAbsoluteFilePath()))) {
		  $this->errorMsg = "Erreur envoi fichier";
		} else {
		  $ret_value = true;
		}
	  } else {
		$this->errorMsg = "Ce fichier n'est pas/plus disponible.";
	  }
	}

	return $ret_value;
  }


  /**********************/
  /* Méthodes statiques */
  /**********************/

  /**
   * \brief Récupération des id des fichiers associés à un lot
   * \param id integer Numéro d'identifiant du lot
   * \return Un tableau contenant les id des fichiers
   */
  public static function getFilesIdForBatch($id) {
	$tabFiles = array();

	if (is_numeric($id)) {
	  $sql = "SELECT id FROM actes_batch_files WHERE batch_id=" . $id . " ORDER BY id";

	  $db =DatabasePool::getInstance();

	  $result = $db->select($sql);

	  if (! $result->isError()) {
		while ($row = $result->get_next_row()) {
		  $tabFiles[] = $row["id"];
		}
	  }
	}

    return $tabFiles;
  }
}
?>
