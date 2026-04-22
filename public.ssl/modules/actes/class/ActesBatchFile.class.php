<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Helpers;

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

class ActesBatchFile extends DataObject
{
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


    /**
     * ActesBatchFile constructor.
     * @param bool|int $id Numéro d'identifiant d'un fichier de lot existant avec lequel initialiser l'objet
     */
    public function __construct($id = false)
    {
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
    public function isProcessed()
    {
        if (strcmp($this->status, 'PRO') == 0) {
            return true;
        } else {
            return false;
        }
    }

  /**
   * \brief Positionnement du statut sur "Non traité"
   */
    public function setUnprocessed()
    {
        $this->status = 'UNPRO';
    }

  /**
   * \brief Positionnement du statut sur "Traité"
   */
    public function setProcessed()
    {
        $this->status = 'PRO';
    }

  /**
   * \brief Méthode d'obtention du chemin absolu vers le fichier
   * \return Chemin du fichier sous forme de chaîne
   */
    public function getAbsoluteFilePath()
    {
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
    public function getDisplayName()
    {
        return basename($this->filename);
    }

  /**
   * \brief Mise en place du fichier dans le répertoire de stockage du lot
   * \return True en cas de succès, false sinon
   */
    public function putTempFileInStorageDir()
    {
        if (! rename($this->tmpFile, $this->getAbsoluteFilePath())) {
            return false;
        }

        return true;
    }

  /**
   * \brief Suppression physique du fichier du lot
   * \return True en cas de succès, false sinon
   */
    public function deleteFile()
    {
        return \S2lowLegacy\Class\Helpers\FileSystemHelper::deleteFromFS(ACTES_BATCHES_UPLOAD_ROOT . "/" . $this->filename);
    }


    /**
     * Méthode d'enregistrement d'un lot dans la base de données // EP - 2018-10-17 : non visiblement ca a pas l'air d'être ça...
     * @param bool $validate Demande la validation ou non des données de l'entité avant enregistrement (true par défaut)
     * @param bool $return_rather_than_exec - pas utilisé, juste pour assurer la compatibilité avec la fonction de la classe mère
     * @return bool|string true si succès, false sinon
     */
    public function save($validate = true, $return_rather_than_exec = false)
    {
        if ($this->isNew()) {
            if (empty($this->storage_dir) || empty($this->tmpFile) || empty($this->tmpName)) {
                $this->errorMsg = "Informations manquantes pour la création du fichier de lot.";
                var_dump($this->storage_dir);
                var_dump($this->tmpFile);
                var_dump($this->tmpName);
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
     * Méthode de suppression d'un fichier de lot
     * @param bool $id - Pas utilisé, uniquemnet pour la compatibilité
     * @return bool true si succès, false sinon
     */
    public function delete($id = false)
    {
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
    public function sendFile()
    {
        $ret_value = false;

        if (isset($this->filename)) {
            if (file_exists($this->getAbsoluteFilePath())) {
                if (! \S2lowLegacy\Class\Helpers\ResponseHelper::sendFileToBrowser($this->getAbsoluteFilePath(), basename($this->getAbsoluteFilePath()), @mime_content_type($this->getAbsoluteFilePath()))) {
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
     * Récupération des id des fichiers associés à un lot
     * @param int $id Numéro d'identifiant du lot
     * @return array Un tableau contenant les id des fichiers
     * @throws Exception
     */
    public static function getFilesIdForBatch($id)
    {
        $tabFiles = array();

        if (is_numeric($id)) {
            $sql = "SELECT id FROM actes_batch_files WHERE batch_id=? ORDER BY id";

            $db = DatabasePool::getInstance();

            $result = $db->select($sql, [$id]);

            if (! $result->isError()) {
                while ($row = $result->get_next_row()) {
                    $tabFiles[] = $row["id"];
                }
            }
        }

        return $tabFiles;
    }
}
