<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\User;

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
class ActesBatch extends DataObject
{
    protected $objectName = "actes_batches";

    protected $description;
    protected $storage_dir;
    protected $submission_date;
    protected $user_id;
    protected $num_prefix;
    protected $next_suffix;

    protected $dbFields = array(
        "description" => array(
            "descr" => "Intitulé du lot",
            "type" => "isString",
            "mandatory" => true
        ),
        "storage_dir" => array(
            "descr" => "Répertoire de stockage sur le serveur",
            "type" => "isString",
            "mandatory" => false
        ),
        "submission_date" => array(
            "descr" => "Date de soumission du lot",
            "type" => "isDate",
            "mandatory" => true
        ),
        "user_id" => array(
            "descr" => "Utilisateur propriétaire du lot",
            "type" => "isInt",
            "mandatory" => true
        ),
        "num_prefix" => array(
            "descr" => "Préfixe numéro interne transactions",
            "type" => "isString",
            "mandatory" => false
        ),
        "next_suffix" => array(
            "descr" => "Prochain suffixe du numéro interne",
            "type" => "isInt",
            "mandatory" => false
        )
    );

    /** @var ActesBatchFile[] */
    protected $batchFiles;
    protected $unprocessedBatchFiles;
    private bool $asNew;
    private LoggerInterface $logger;

    /**
     * Constructeur d'un lot
     * ActesBatch constructor.
     * @param bool|int $id Numéro d'identifiant d'un lot existant avec lequel initialiser l'objet
     */
    public function __construct($id = false, bool $asNew = false)
    {
        $this->asNew = $asNew;
        $this->logger = LegacyObjectsManager::getLegacyObjectInstancier()->get(LoggerInterface::class);
        parent:: __construct($id);
    }

    /**
     * \brief Méthode initialisant l'entité avec l'identifiant courant
     * \return true si succès, false sinon
     */
    public function init()
    {
        return $this->initBatchFiles() && parent:: init();
    }

    /**
     * \brief Initialisation du répertoire de stockage (création)
     * \return True en cas de succès, false sinon
     */
    public function initStorage()
    {
        if (isset($this->user_id) && isset($this->id)) {
            $owner = new User($this->user_id);
            $owner->init();
            $authority = new Authority($owner->get("authority_id"));
            $authority->init();

            $this->storage_dir = $authority->get("siren") . "/" . $this->id;

            if (
                $this->storage_dir == "/" || !Helpers:: createDirTree(
                    ACTES_BATCHES_UPLOAD_ROOT . "/" . $this->storage_dir,
                    ACTES_BATCHES_UPLOAD_ROOT
                )
            ) {
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
    public function purgeStorage()
    {
        foreach ($this->batchFiles as $batchFile) {
            if (!$batchFile->deleteFile()) {
                return false;
            }
        }

        if (!Helpers:: deleteFromFS(ACTES_BATCHES_UPLOAD_ROOT . "/" . $this->storage_dir)) {
            return false;
        }

        return true;
    }

    /**
     * \brief Suppression du répertoire de stockage
     * \return True en cas de succès, false sinon
     */
    public function deleteStorage()
    {
        return Helpers:: deleteFromFS(ACTES_BATCHES_UPLOAD_ROOT . "/" . $this->storage_dir);
    }

    /**
     * \brief Initialisation des fichiers associés à un lot
     */
    public function initBatchFiles()
    {
        if (isset($this->id)) {
            $this->batchFiles = array();

            $fileIds = ActesBatchFile:: getFilesIdForBatch($this->id);

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
    public function getBatchFiles()
    {
        if (!isset($this->batchFiles)) {
            $this->initBatchFiles();
        }

        return $this->batchFiles;
    }

    /**
     * Méthode d'obtention de la liste des lots et tous leurs attributs
     * @param string $cond Chaîne contenant les conditions (SQL) à appliquer à la fin de la requête BDD
     * @return bool|array Tableau des lots
     */
    public function getBatchesList($cond = "")
    {
        if (
            !$this->pagerInit(
                'DISTINCT actes_batches.id, actes_batches.user_id, actes_batches.submission_date, actes_batches.storage_dir, actes_batches.num_prefix, actes_batches.description',
                'actes_batches',
                $cond
            )
        ) {
            return false;
        }

        return $this->data;
    }

    /**
     * Méthode d'obtention de la liste des lots et tous leurs attributs pour un utilisateur
     * @param int $user_id Identifiant de l'utilisateur
     * @return array|bool Tableau des lots
     */
    public function getBatchesListForUser($user_id)
    {
        if (is_numeric($user_id)) {
            return $this->getBatchesList(" WHERE actes_batches.user_id=" . $user_id);
        }

        return array();
    }

    /**
     * \brief Méthode de rafraichissement de la liste des fichiers du lot restant à traiter
     * \return Tableau des fichiers restant à traiter
     */
    private function refreshUnprocessedFiles()
    {
        if (!isset($this->batchFiles)) {
            $this->initBatchFiles();
        }

        $this->unprocessedBatchFiles = array();

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
    public function getUnprocessedFiles()
    {
        if (!isset($this->unprocessedBatchFiles)) {
            $this->refreshUnprocessedFiles();
        }

        return $this->unprocessedBatchFiles;
    }

    /**
     * \brief Méthode d'obtention du nombre de fichiers du lot restant à traiter
     * \return Le nombre de fichier restant à traiter dans le lot
     */
    public function getUnprocessedFilesCount()
    {
        if (!isset($this->unprocessedBatchFiles)) {
            $this->refreshUnprocessedFiles();
        }

        return count($this->unprocessedBatchFiles);
    }

    /**
     * \brief Méthode d'obtention du nombre de fichiers du lot
     * \return Le nombre de fichier dans le lot
     */
    public function getAllFilesCount()
    {
        if (!isset($this->batchFiles)) {
            $this->initBatchFiles();
        }

        return count($this->batchFiles);
    }

    /**
     * \brief Méthode d'obtention de la liste des fichiers du lot restant à traiter sous forme id/name
     * \return Tableau des fichiers restant à traiter, clef=id, valeur=nom fichier
     */
    public function getUnprocessedFilesIdName()
    {
        if (!isset($this->unprocessedBatchFiles)) {
            $this->refreshUnprocessedFiles();
        }

        return $this->getFilesIdName($this->unprocessedBatchFiles);
    }

    /**
     * \brief Méthode d'obtention de la liste des fichiers du lot sous forme id/name
     * \return Tableau des fichiers, clef=id, valeur=nom fichier
     */
    public function getAllFilesIdName()
    {
        if (!isset($this->batchFiles)) {
            $this->initBatchFiles();
        }

        return $this->getFilesIdName($this->batchFiles);
    }

    /**
     * Méthode d'obtention de la liste de fichiers du lot sous forme id/name
     * @param array $files Tableau d'objet ActesBatchFiles
     * @return array Tableau des fichiers, clef=id, valeur=nom fichier
     */
    private function getFilesIdName($files)
    {
        $retFiles = array();

        if (is_array($files)) {
            foreach ($files as $file) {
                $retFiles[$file->getId()] = basename($file->get("filename"));
            }
        }

        return $retFiles;
    }

    /**
     * Récupération du prochain Id de fichier à traiter
     * @param null|int $currentId identifiant du fichier courant
     * @return null|int L'identifiant du prochain fichier à traiter ou null si plus de fichier à traiter
     */
    public function getNextUnprocessedId($currentId = null)
    {
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
                    $newPos = ($pos + 1) % count($fileId);
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
     * \brief
     * \return
     */

    /**
     * Récupération du prochain suffixe pour le numéro interne
     * @return bool|int|null|string Le suffixe pour le numéro interne
     * @throws Exception
     */
    public function getNextSuffix()
    {
        if (isset($this->id)) {
            $next_suffix = 1;

            // On récupère le suffixe
            $sql = "SELECT next_suffix FROM actes_batches WHERE id= ? FOR UPDATE";

            $result = $this->db->select($sql, [$this->id]);

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
     * Incrémentaiton du prochain suffixe pour le numéro interne
     * @return bool|int|null|string
     * @throws Exception
     */
    public function incNextSuffix()
    {
        if (isset($this->id)) {
            $next_suffix = $this->getNextSuffix();

            if ($next_suffix != null) {
                $sql = "UPDATE actes_batches SET next_suffix= ? WHERE id= ?";

                if (!$this->db->exec($sql, [$next_suffix + 1, $this->id])) {
                    $this->errorMsg = "Erreur d'accès base de données.";
                    return $this->errorMsg;
                }
                return $this->getNextSuffix();
            } else {
                $this->errorMsg = "Impossible de récupérer le suffixe courant.";
            }
            return $this->errorMsg;
        }
        return false;
    }

    /**
     * Méthode d'importation des fichiers depuis les parametres du formulaire
     * @param array $files tableau des fichiers (format $_FILE)
     * @return bool True en cas de succès ou False sinon
     */
    public function importFilesFromForm($files)
    {
        $ret_value = true;

        if (is_array($files)) {
            // Copie du tableau pour recherche des signatures
            $filesDup = $files;

            $this->errorMsg = "<span style='font-weight:bold;color:red;'>Echec de la cr&eacute;ation du lot.</span><br />\n";

            reset($files);
            foreach ($files as $file) {
                $filename = str_replace("\'", "-", $file['name']);
                $filename = str_replace("'", "-", $filename);
                $filename = str_replace("?", "-", $filename);
                /*if (strstr($filename,"?"))
                {
                $this->errorMsg .= "Le fichier " . $filename . " ne doit pas contenir des lettres accentuées";
                $ret_value = false;
                }*/
                //echo $fiename;
                //$filename = Helpers :: stripSlashes($file["name"]);

                // On ne traite pas individuellement les fichiers de signature
                if (!preg_match("/\.sig$/", $filename)) {
                    if (is_uploaded_file_wrapper($file["tmp_name"])) {
                        // Vérification du type de fichier
                        $type = Helpers:: getFileType($file["tmp_name"]);
                        $this->logger->info(
                            sprintf(
                                "Récuperation de %s - type : %s - tmp_name : %s",
                                $file["name"],
                                $type,
                                $file["tmp_name"]
                            )
                        );


                        if ($type != "application/pdf") {
                            $this->errorMsg .= "Le fichier <span style='font-weight:bold;'>" . $filename . "</span> n'est pas du type requis ($type). <span style='font-weight:bold;'>Seuls les fichiers PDF sont autoris&eacute;s.</span><br />\n";
                            $ret_value = false;
                        } else {
                            // On recherche un fichier de signature associé
                            $foundSig = false;
                            $sign = "";
                            reset($filesDup);
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
     * Méthode d'ajout d'un fichier dans le lot
     * @param string $filepath chemin vers le fichier dans le système de fichier
     * @param string $name nom du fichier final
     * @param string $sign signature électronique du fichier
     * @return bool True en cas de succès ou False sinon
     */
    public function addBatchFile($filepath, $name, $sign)
    {
        $batchF = new ActesBatchFile();

        $batchF->set("signature", $sign);
        $batchF->set("tmpName", $name);
        $batchF->set("tmpFile", $filepath);
        $batchF->setUnprocessed();

        $this->batchFiles[] = $batchF;

        return true;
    }

    /**
     * Méthode d'enregistrement d'un lot dans la base de données
     * @param bool $validate Demande la validation ou non des données de l'entité avant enregistrement (true par défaut)
     * @param bool $return_rather_than_exec : Attention, ce paramètre ne sert pas et est utilisé que pour la compatibilité
     * @return bool|string true si succès, false sinon
     * @throws Exception
     */
    public function save($validate = true, $return_rather_than_exec = false)
    {
        $new = false;
        if ($this->isNew()) {
            $new = true;

            if (empty($this->description)) {
                $this->description = "Lot posté le " . date("d-m-Y");
            }

            $this->submission_date = date("Y-m-d H:i:s");
        }

        $saveSQLRequest = parent::buildSaveSQLRequest($validate);
        if (!$saveSQLRequest->isValid()) {
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

        if (!$this->db->exec($saveSQLRequest->getRequest(), $saveSQLRequest->getParams())) {
            $this->errorMsg = "Erreur lors de la sauvegarde du lot.";
            $this->db->rollback();
            return false;
        }

        // Définition du répertoire de stockage
        if (
            !$this->db->exec(
                "UPDATE actes_batches SET storage_dir=? WHERE id= ?",
                [$this->storage_dir, $this->id]
            )
        ) {
            $this->errorMsg = "Erreur lors de la définition du répertoire de stockage du lot.";
            $this->db->rollback();
            return false;
        }

        if (is_null($this->batchFiles)) {
            $this->batchFiles = [];
        }

        foreach ($this->batchFiles as $batchFile) {
            if ($new || $this->asNew) {
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
     *
     * Méthode de suppression d'un lot
     * @param bool $id - Ne sert pas, uniquement pour assurer la compatibilité
     * @return bool
     * @throws Exception
     */
    public function delete($id = false)
    {
        if (!$this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

        // Suppression des fichiers inclus
        if (!isset($this->batchFiles)) {
            $this->initBatchFiles();
        }

        foreach ($this->batchFiles as $batchFile) {
            if (!$batchFile->delete()) {
                $this->errorMsg = "Erreur lors de la suppression des fichiers associés au lot : " . $batchFile->getErrorMsg(
                );
                $this->db->rollback();
                return false;
            }
        }

        // Suppression du répertoire de stockage
        if (!$this->deleteStorage()) {
            return false;
        }

        if (!parent:: delete()) {
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
