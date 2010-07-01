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
/**
 * \class ActesBatch ActesBatch.class.php
 * \brief Cette classe permet de gérer les lots de transactions ACTES
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 02.02.2007
 * 
 *
 * Cette classe fournit des méthodes de gestion des lots de transactions
 * Actes
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once (SITEROOT . "/class/DataObject.class.php");
require_once (SITEROOT . "/class/Parapheur.class.php");
require_once (SITEROOT . "/public.ssl/modules/actes/class/ActesBatchFile.class.php");

class ActesBatch extends DataObject {
  protected $objectName = "actes_batches";

  protected $description;
  protected $storage_dir;
  protected $submission_date;
  protected $user_id;
  protected $num_prefix;
  protected $next_suffix;

  protected $dbFields = array (
    "description" => array (
      "descr" => "Intitulé du lot",
      "type" => "isString",
      "mandatory" => true
    ),
    "storage_dir" => array (
      "descr" => "Répertoire de stockage sur le serveur",
      "type" => "isString",
      "mandatory" => false
    ),
    "submission_date" => array (
      "descr" => "Date de soumission du lot",
      "type" => "isDate",
      "mandatory" => true
    ),
    "user_id" => array (
      "descr" => "Utilisateur propriétaire du lot",
      "type" => "isInt",
      "mandatory" => true
    ),
    "num_prefix" => array (
      "descr" => "Préfixe numéro interne transactions",
      "type" => "isString",
      "mandatory" => false
    ),
    "next_suffix" => array (
      "descr" => "Prochain suffixe du numéro interne",
      "type" => "isInt",
      "mandatory" => false
    )
  );

  protected $batchFiles;
  protected $unprocessedBatchFiles;

  /**
   * \brief Constructeur d'un lot
   * \param id integer Numéro d'identifiant d'un lot existant avec lequel initialiser l'objet
   */
  public function __construct($id = false) {
    parent :: __construct($id);
  }

  /**
   * \brief Méthode initialisant l'entité avec l'identifiant courant
   * \return true si succès, false sinon
  */
  public function init() {
    return $this->initBatchFiles() && parent :: init();
  }

  /**
   * \brief Initialisation du répertoire de stockage (création)
   * \return True en cas de succès, false sinon
   */
  public function initStorage() {
    if (isset ($this->user_id) && isset ($this->id)) {
      $owner = new User($this->user_id);
      $owner->init();
      $authority = new Authority($owner->get("authority_id"));
      $authority->init();

      $this->storage_dir = $authority->get("siren") . "/" . $this->id;

      if ($this->storage_dir == "/" || !Helpers :: createDirTree(ACTES_BATCHES_UPLOAD_ROOT . "/" . $this->storage_dir, ACTES_BATCHES_UPLOAD_ROOT)) {
        $this->errorMsg = "Erreur de création du répertoire de stockage du lot.";
        return false;
      }
    } else {
      return false;
    }

    return true;
  }

  /**
   * \brief Nettoyage du répertoire de stockage
   * \return True en cas de succès, false sinon
   */
  public function purgeStorage() {
    foreach ($this->batchFiles as $batchFile) {
      if (!$batchFile->deleteFile()) {
        return false;
      }
    }

    if (!Helpers :: deleteFromFS(ACTES_BATCHES_UPLOAD_ROOT . "/" . $this->storage_dir)) {
      return false;
    }

    return true;
  }

  /**
   * \brief Suppression du répertoire de stockage
   * \return True en cas de succès, false sinon
   */
  public function deleteStorage() {
    return Helpers :: deleteFromFS(ACTES_BATCHES_UPLOAD_ROOT . "/" . $this->storage_dir);
  }

  /**
   * \brief Initialisation des fichiers associés à un lot
   */
  public function initBatchFiles() {
    if (isset ($this->id)) {
      $this->batchFiles = array ();

      $fileIds = ActesBatchFile :: getFilesIdForBatch($this->id);

      foreach ($fileIds as $fileId) {
        $batchFile = new ActesBatchFile($fileId);

        if ($batchFile->init()) {
          $this->batchFiles[] = $batchFile;
        }
      }

      return true;
    } else {
      return false;
    }
  }

  /**
   * \brief Récupération des fichiers associés à un lot
   * \return Un tableau de ActesBatchFile associé au lot
   */
  public function getBatchFiles() {
    if (!isset ($this->batchFiles)) {
      $this->initBatchFiles();
    }

    return $this->batchFiles;
  }

  /**
   * \brief Méthode d'obtention de la liste des lots et tous leurs attributs
   * \param $cond (optionnel) chaîne : Chaîne contenant les conditions (SQL) à appliquer à la fin de la requête BDD
   * \return Tableau des lots
   */
  public function getBatchesList($cond = "") {
    if (!$this->pagerInit('DISTINCT actes_batches.id, actes_batches.user_id, actes_batches.submission_date, actes_batches.storage_dir, actes_batches.num_prefix, actes_batches.description', 'actes_batches', $cond)) {
      return false;
    }

    return $this->data;
  }

  /**
   * \brief Méthode d'obtention de la liste des lots et tous leurs attributs pour un utilisateur
   * \param $user_id integer : Identifiant de l'utilisateur
   * \return Tableau des lots
   */
  public function getBatchesListForUser($user_id) {
    if (is_numeric($user_id)) {
      return $this->getBatchesList(" WHERE actes_batches.user_id=" . $user_id);
    }

    return array ();
  }

  /**
   * \brief Méthode de rafraichissement de la liste des fichiers du lot restant à traiter
   * \return Tableau des fichiers restant à traiter
   */
  private function refreshUnprocessedFiles() {
    if (!isset ($this->batchFiles)) {
      $this->initBatchFiles();
    }

    $this->unprocessedBatchFiles = array ();

    foreach ($this->batchFiles as $file) {
      if (!$file->isProcessed()) {
        $this->unprocessedBatchFiles[] = $file;
      }
    }
  }

  /**
   * \brief Méthode d'obtention de la liste des fichiers du lot restant à traiter
   * \return Tableau des fichiers restant à traiter
   */
  public function getUnprocessedFiles() {
    if (!isset ($this->unprocessedBatchFiles)) {
      $this->refreshUnprocessedFiles();
    }

    return $this->unprocessedBatchFiles;
  }

  /**
   * \brief Méthode d'obtention du nombre de fichiers du lot restant à traiter
   * \return Le nombre de fichier restant à traiter dans le lot
   */
  public function getUnprocessedFilesCount() {
    if (!isset ($this->unprocessedBatchFiles)) {
      $this->refreshUnprocessedFiles();
    }

    return count($this->unprocessedBatchFiles);
  }

  /**
   * \brief Méthode d'obtention du nombre de fichiers du lot
   * \return Le nombre de fichier dans le lot
   */
  public function getAllFilesCount() {
    if (!isset ($this->batchFiles)) {
      $this->initBatchFiles();
    }

    return count($this->batchFiles);
  }

  /**
   * \brief Méthode d'obtention de la liste des fichiers du lot restant à traiter sous forme id/name
   * \return Tableau des fichiers restant à traiter, clef=id, valeur=nom fichier
   */
  public function getUnprocessedFilesIdName() {
    if (!isset ($this->unprocessedBatchFiles)) {
      $this->refreshUnprocessedFiles();
    }

    return $this->getFilesIdName($this->unprocessedBatchFiles);
  }

  /**
   * \brief Méthode d'obtention de la liste des fichiers du lot sous forme id/name
   * \return Tableau des fichiers, clef=id, valeur=nom fichier
   */
  public function getAllFilesIdName() {
    if (!isset ($this->batchFiles)) {
      $this->initBatchFiles();
    }

    return $this->getFilesIdName($this->batchFiles);
  }

  /**
   * \brief Méthode d'obtention de la liste de fichiers du lot sous forme id/name
   * \param $files Tableau d'objet ActesBatchFiles
   * \return Tableau des fichiers, clef=id, valeur=nom fichier
   */
  private function getFilesIdName($files) {
    $retFiles = array ();

    if (is_array($files)) {
      foreach ($files as $file) {
        $retFiles[$file->getId()] = basename($file->get("filename"));
      }
    }

    return $retFiles;
  }

  /**
   * \brief Récupération du prochain Id de fichier à traiter
   * \param $currentId entier (optionnel) : identifiant du fichier courant
   * \return L'identifiant du prochain fichier à traiter ou null si plus de fichier à traiter
   */
  public function getNextUnprocessedId($currentId = null) {
    $nextId = null;

    $this->initBatchFiles();

    $this->refreshUnprocessedFiles();
    $files = $this->getUnprocessedFilesIdName();

    if (count($files) > 0) {
      $fileId = array_keys($files);

      $pos = 0;
      $newPos = 0;
      if ($currentId) {
        $pos = array_search($currentId, $fileId);

        if ($pos !== false) {
          $newPos = ($pos +1) % count($fileId);
        }
      }

      if ($currentId && $newPos == $pos) {
        $nextId = null;
      } else {
        $nextId = $fileId[$newPos];
      }
    }

    return $nextId;
  }

  /**
   * \brief Récupération du prochain suffixe pour le numéro interne
   * \return Le suffixe pour le numéro interne
   */
  public function getNextSuffix() {
    if (isset ($this->id)) {
      $next_suffix = 1;

      // On récupère le suffixe
      $sql = "SELECT next_suffix FROM actes_batches WHERE id=" . $this->id . " FOR UPDATE";

      $result = $this->db->select($sql);

      if (!$result->isError()) {
        if ($result->num_row() > 0) {
          $row = $result->get_next_row();
          if (is_numeric($row["next_suffix"])) {
            $next_suffix = $row["next_suffix"];
          }
        }
      } else {
        return false;
      }
      return $next_suffix;
    } else {
      return null;
    }
  }

  /**
   * \brief Incrémentaiton du prochain suffixe pour le numéro interne
   */
  public function incNextSuffix() {
    if (isset ($this->id)) {
      $next_suffix = $this->getNextSuffix();

      if ($next_suffix != null) {
        $sql = "UPDATE actes_batches SET next_suffix=" . ($next_suffix +1) . " WHERE id=" . $this->id;

        if (!$this->db->exec($sql)){
          $this->errorMsg = "Erreur d'accès base de données.";
          return $this->errorMsg;
        }
        return $this->getNextSuffix();
      } else
        $this->errorMsg = "Impossible de récupérer le suffixe courant.";
        return $this->errorMsg;
    }
  }

  /**
   * \brief Méthode d'importation des fichiers depuis les parametres du formulaire
   * \param $files tableau des fichiers (format $_FILE)
   * \return True en cas de succès ou False sinon
   */
  public function importFilesFromForm($files) {
    $ret_value = true;

    if (is_array($files)) {

      // Copie du tableau pour recherche des signatures
      $filesDup = $files;

      $this->errorMsg = "";

      reset($files);
      while ((list ($key, $file) = each($files))) {
        
      	$filename=str_replace("\'","-",$file['name']);
      	$filename=str_replace("'","-",$filename);
		if (strstr($filename,"?"))
		{	
			$this->errorMsg .= "Le fichier " . $filename . " ne doit pas contenir des lettres accentuées";
			$ret_value = false;	
		}
      	//echo $fiename;
        //$filename = Helpers :: stripSlashes($file["name"]);

        // On ne traite pas individuellement les fichiers de signature
        if (!preg_match("/\.sig$/", $filename)) {
          if (is_uploaded_file($file["tmp_name"])) {
            // Vérification du type de fichier
            $type = Helpers :: getFileType($file["tmp_name"]);
          	$trace = Trace::getInstance();
          	
          	$trace->log("Récuperation de ". $file['name'] . " - type : " .$type  . " - tmp_name : " . $file["tmp_name"]);
          	
          	
            if ( $type != "application/pdf") {
              $this->errorMsg .= "Le fichier " . $filename . " n'est pas du type requis($type). Seul les fichiers PDF sont autorisés.\n";
              $ret_value = false;
            } else {
              // On recherche un fichier de signature associé
              $foundSig = false;
              $sign = "";
              reset($filesDup);
              while ((list ($key2, $sigFile) = each($filesDup)) && !$foundSig) {
                // on recherche un fichier de même nom que le fichier courant avec .sig à la fin
                if (strcmp($sigFile["name"], $filename . ".sig") == 0) {
                  $foundSig = true;

                  if (is_uploaded_file($sigFile["tmp_name"])) {
                    // Vérification de la signature
                    $parapheur = new Parapheur(file_get_contents($file["tmp_name"]));
                    $ret = $parapheur->verify(file_get_contents($sigFile["tmp_name"]));
                    
                    if (!$ret) {
                      $this->errorMsg .= "La signature numérique du fichier " . $filename . " est incorrecte : " . $parapheur->getLastError() . "\n";
                      $ret_value = false;
                    }
                    elseif (! $sign = file_get_contents($sigFile["tmp_name"])) {
                      $this->errorMsg .= "Erreur de récupération de la signature du fichier " . $filename . "\n";
                      $ret_value = false;
                    }
                  } else {
                    $this->errorMsg .= "Envoi de fichier incorrect.";
                    $ret_value = false;
                  }
                }
              }
            }

            if ($ret_value) {
              if (!$this->addBatchFile($file["tmp_name"], $filename, $sign)) {
                $ret_value = false;
              }
            }
          } else {
            $this->errorMsg .= "Envoi de fichier incorrect.<br />";
            $ret_value = false;
          }
        }
      }
    } else {
      $ret_value = false;
    }

    return $ret_value;
  }

  /**
   * \brief Méthode d'ajout d'un fichier dans le lot
   * \param $filepath chemin vers le fichier dans le système de fichier
   * \param $name nom du fichier final
   * \param $sign chaine : signature électronique du fichier
   * \return True en cas de succès ou False sinon
   */
  public function addBatchFile($filepath, $name, $sign) {
    $batchF = new ActesBatchFile();

    $batchF->set("signature", $sign);
    $batchF->set("tmpName", $name);
    $batchF->set("tmpFile", $filepath);
    $batchF->setUnprocessed();

    $this->batchFiles[] = $batchF;

    return true;
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

      if (empty ($this->description)) {
        $this->description = "Lot posté le " . date("d-m-Y");
      }

      $this->submission_date = date("Y-m-d H:i:s");
    }

    if (!($sql = parent :: save($validate, true))) {
      return false;
    }

    if ($new) {
      // Si nouveau lot, création répertoire de stockage
      if (!$this->initStorage()) {
        return false;
      }
    }

    //echo $sql;
    //exit();

    if (!$this->db->begin()) {
      $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
      return false;
    }

    if (!$this->db->exec($sql)) {
      $this->errorMsg = "Erreur lors de la sauvegarde du lot.";
      $this->db->rollback();
      return false;
    }

    // Définition du répertoire de stockage
    if (!$this->db->exec("UPDATE actes_batches SET storage_dir='" . $this->storage_dir . "' WHERE id=" . $this->id)) {
      $this->errorMsg = "Erreur lors de la définition du répertoire de stockage du lot.";
      $this->db->rollback();
      return false;
    }

    foreach ($this->batchFiles as $batchFile) {
      if ($new) {
        $batchFile->set("batch_id", $this->id);
        $batchFile->set("storage_dir", $this->storage_dir);
      }

      if (!$batchFile->save()) {
        $this->errorMsg = "Erreur d'enregistrement d'un fichier du lot : " . $batchFile->getErrorMsg();
        $this->db->rollback();

        if ($new) {
          $this->purgeStorage();
        }

        return false;
      }
    }

    if (!$this->db->commit()) {
      $this->errorMsg = "Erreur lors de la validation de la transaction.";
      $this->db->rollback();

      if ($new) {
        $this->purgeStorage();
      }

      return false;
    }

    return true;
  }

  /**
   * \brief Méthode de suppression d'un lot
   * \return true si succès, false sinon
  */
  public function delete() {
    if (!$this->db->begin()) {
      $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
      return false;
    }

    // Suppression des fichiers inclus
    if (!isset ($this->batchFiles)) {
      $this->initBatchFiles();
    }

    foreach ($this->batchFiles as $batchFile) {
      if (!$batchFile->delete()) {
        $this->errorMsg = "Erreur lors de la suppression des fichiers associés au lot : " . $batchFile->getErrorMsg();
        $this->db->rollback();
        return false;
      }
    }

    // Suppression du répertoire de stockage
    if (!$this->deleteStorage()) {
      return false;
    }

    if (!parent :: delete()) {
      return false;
    }

    if (!$this->db->commit()) {
      $this->errorMsg = "Erreur lors de la validation de la transaction.";
      $this->db->rollback();
      return false;
    }

    return true;
  }
}
?>
