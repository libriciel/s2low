<?php

use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\XMLHelper;

class ActesEnvelope extends DataObject
{
    protected $objectName = "actes_envelopes";

    protected $user_id;
    protected $siren;
    protected $submission_date;
    protected $department;
    protected $district;
    protected $authority_type_code;
    protected $name;
    protected $telephone;
    protected $email;
    protected $file_path;
    protected $file_size;
    protected $return_mail;
    protected $transactions = array();
    protected $rootDir;
    protected $destDir;
    protected $tmpDir;
    protected $dbFields = array( "user_id" => array( "descr" => "Identifiant utilisateur", "type" => "isInt", "mandatory" => true),
                         "siren" => array( "descr" => "Code SIREN de la collectivité", "type" => "isString", "mandatory" => true),
                         "submission_date" => array( "descr" => "Date de dépôt de l'enveloppe", "type" => "isDate", "mandatory" => false),
                         "department" => array( "descr" => "Département de la collectivité", "type" => "isString", "mandatory" => true),
                         "district" => array( "descr" => "Arrondissement de la collectivité", "type" => "isString", "mandatory" => true),
                         "authority_type_code" => array( "descr" => "Code nature de la collectivité", "type" => "isString", "mandatory" => true),
                         "return_mail" => array( "descr" => "Adresses électroniques pour la réponse", "type" => "isString", "mandatory" => true),
                         "name" => array( "descr" => "Nom de l'interlocuteur", "type" => "isString", "mandatory" => false),
                         "telephone" => array( "descr" => "Téléphone de l'interlocuteur", "type" => "isString", "mandatory" => false),
                         "email" => array( "descr" => "Adresse électronique de l'interlocuteur", "type" => "isString", "mandatory" => false),
                         "file_path" => array( "descr" => "Chemin vers l'archive .tar.gz", "type" => "isString", "mandatory" => false),
                         "file_size" => array( "descr" => "Taille de l'archive .tar.gz", "type" => "isInt", "mandatory" => false),
                         );
    private $envelope_id;
    private $envXmlFile;
    private $envXmlFileSize;
    private $envXmlObj;
    private $fileList;

  /**
   * \brief Constructeur d'une enveloppe
   * \param id integer Numéro d'identifiant d'une enveloppe existante avec laquelle initialiser l'objet
   */
    public function __construct($id = false)
    {
        parent::__construct($id);
    }

  /**
   * \brief Méthode d'obtention de la liste des identifiants des enveloppes
   * \param $cond (optionnel) chaîne : Chaîne contenant les conditions (SQL) à appliquer à la fin de la requête BDD
   * \param $transmitted_only booléen (optionnel) : Ne considère que les enveloppes réellement transmises si true (false par défaut)
   * \return Tableau des identifiants des enveloppes
  */
    public static function getEnvelopesId($cond = false, $transmitted_only = false)
    {
        $sql = "SELECT DISTINCT actes_envelopes.id FROM actes_envelopes"
        . " LEFT JOIN users ON actes_envelopes.user_id=users.id"
        . " LEFT JOIN actes_transactions ON actes_transactions.envelope_id=actes_envelopes.id"
        . " LEFT JOIN actes_transactions_workflow ON actes_transactions_workflow.transaction_id=actes_transactions.id";

        if ($transmitted_only) {
            if ($cond) {
                $cond .= " AND ";
            } else {
                $cond = "";
            }

            $cond .= " actes_transactions_workflow.status_id=3";
        }

        if ($cond) {
            $sql .= " WHERE " . $cond;
        }

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        if (! $result->isError()) {
            return $result->get_all_rows();
        }

        return false;
    }

  /**
   * \brief Méthode d'obtention du volume transmis depuis une période donnée ou depuis toujours
   * \param $cond (optionnel) chaîne : Chaîne contenant les conditions (SQL) à appliquer à la fin de la requête BDD
   * \param $transmitted_only booléen (optionnel) : Ne considère que les enveloppes réellement transmises si true (false par défaut)
   * \return Volume en octet correspondant à la demande
  */
    public static function getEnvelopesVolume($cond = false, $transmitted_only = false)
    {
        $sql = "SELECT SUM(actes_envelopes.file_size) AS sum FROM actes_envelopes actes_envelopes"
        . " LEFT JOIN users ON actes_envelopes.user_id=users.id"
        . " LEFT JOIN actes_transactions ON actes_transactions.envelope_id=actes_envelopes.id"
        . " LEFT JOIN actes_transactions_workflow ON actes_transactions_workflow.transaction_id=actes_transactions.id";

        $filter = array();

        if ($cond) {
            $filter[] = $cond;
        }

        if ($transmitted_only) {
            $filter[] = "actes_transactions_workflow.status_id=3";
        } else {
            $filter[] = "actes_transactions_workflow.status_id=1";
        }

      // Pour éviter les doublons
        $filter[] = "actes_transactions.id=(SELECT MIN(actes_transactions.id) FROM actes_transactions WHERE actes_transactions.envelope_id=actes_envelopes.id)";

        $sql .= " WHERE " . implode(" AND ", $filter);

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        if (! $result->isError()) {
            $row = $result->get_next_row();
            return $row["sum"];
        }

        return false;
    }

  /**
   * \brief Méthode d'obtention de la liste des fichiers inclus dans une enveloppe
   * \param $env_id integer : Identifiant de l'enveloppe concernée
   * \return Tableau de noms de fichier
  */
    public static function getEnvelopesIncludedFiles($env_id)
    {
        $sql = "SELECT filename FROM actes_included_files WHERE envelope_id=" . $env_id;

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        if (! $result->isError()) {
            return $result->get_all_rows();
        }

        return false;
    }

  /**
   * \brief Méthode d'obtention d'un historique d'envoi des enveloppes au serveur du ministère
   * \param $authority_id integer (optionnel) : Identifiant de la collectivité expéditrice des enveloppes
   * \return Tableau des données des enveloppes
  */
    public static function getEnvelopesHistory($authority_id = false)
    {
        $sql = "SELECT DISTINCT ae.id, ae.file_path, atw.date, auth.siren, auth.department, auth.district";
        $sql .= " FROM actes_envelopes ae";
        $sql .= " LEFT JOIN users ON ae.user_id=users.id";
        $sql .= " LEFT JOIN authorities auth ON users.authority_id=auth.id";
        $sql .= " LEFT JOIN actes_transactions at ON at.envelope_id=ae.id";
        $sql .= " LEFT JOIN actes_transactions_workflow atw ON atw.transaction_id=at.id";
      // On veut récupérer la date où la transaction a été transmise => statut 3
        $sql .= " WHERE atw.status_id=3";

        if ($authority_id) {
            $sql .= " AND auth.id=" . $authority_id;
        }

        $sql .= " ORDER BY atw.date DESC";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        $env = array();

        if (! $result->isError()) {
            while ($row = $result->get_next_row()) {
                $env[] = $row;
            }
        }

        return $env;
    }

  /**
   * \brief Méthode permettant de fixer la valeur d'un attribut
   * \param $name chaîne : Nom de l'attribut
   * \param $val : valeur de l'attribut
  */
    public function set($name, $val)
    {
        switch ($name) {
            case "destDir":
                parent::set("rootDir", ACTES_FILES_UPLOAD_ROOT);
                break;
        }

        parent::set($name, $val);
    }

  /**
   * \brief Méthode initialisant l'entité avec l'identifiant courant
   * \return true si succès, false sinon
  */
    public function init()
    {
        if (! parent::init()) {
            return false;
        }

        $this->initTransactions();

        return true;
    }

  /**
   * \brief Méthode de récupération des transactions de l'enveloppe courante dans la variable membre $transactions
   */
    public function initTransactions()
    {
        if (isset($this->id)) {
            $this->transactions = ActesEnvelope::getTransactionsForEnvelope($this->envelope_id);
        }
    }

  /**
   * \brief Méthode d'obtention de la liste des transactions pour une enveloppe
   * \param $id integer : Identifiant de l'enveloppe pour laquelle récupérer les transactions
   * \return Tableau d'objet ActesTransaction correspondant à l'enveloppe spécifiée
  */
    public static function getTransactionsForEnvelope($id)
    {
        $transac = array();

        if (! empty($id)) {
            $sql = "SELECT actes_transactions.id FROM actes_transactions WHERE actes_transactions.envelope_id = " . $id;

            $db = DatabasePool::getInstance();

            $result = $db->select($sql);

            if (! $result->isError()) {
                while ($row = $result->get_next_row()) {
                    $obj = new ActesTransaction($row["id"]);
                    if ($obj->init()) {
                        $transac[] = $obj;
                    }
                }
            }
        }

        return $transac;
    }

  /**
   * \brief Méthode de remise à zéro de la liste des transactions pour l'enveloppe courante
   */
    public function resetTransactions()
    {
        $this->transactions = array();
    }

    public function addTransaction($trans)
    {
        $this->transactions[] = $trans;
    }

  /**
   * \brief Méthode de création du fichier XML de l'enveloppe
   * \return True en cas de succès, false sinon
   */
    public function generateEnvelopeXMLFile($serial)
    {

        if (count($this->transactions) <= 0) {
            $this->errorMsg = "Informations manquantes pour générer l'enveloppe (pas de transaction)";
            return false;
        }

        $this->envXmlFile = $this->destDir . "/" . ACTES_APPLI_NAME . "--" . $this->siren . '--' . date('Ymd') . '-' . $serial . '.xml';


        $xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
        $xml .= "<actes:EnveloppeCLMISILL xmlns:actes=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216\" ";
        $xml .= "xmlns:insee=\"http://xml.insee.fr/schema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" ";
        $xml .= "xsi:schemaLocation=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216 actesv1_1.xsd\">\n";

        $xml .= " <actes:Emetteur>\n";
        $xml .= "  <actes:IDCL insee:SIREN=\"" . Helpers::escapeForXML($this->siren) . "\" actes:Departement=\"" . Helpers::escapeForXML($this->department) . "\" actes:Arrondissement=\"" . Helpers::escapeForXML($this->district) . "\" actes:Nature=\"" . Helpers::escapeForXML($this->authority_type_code) . "\"/>\n";
        $xml .= "  <actes:Referent>\n";
        $xml .= "   <actes:Nom>" . XMLHelper::convertToIsoAndEscape($this->name) . "</actes:Nom>\n";
        $xml .= "   <actes:Telephone>" . Helpers::escapeForXML($this->telephone) . "</actes:Telephone>\n";
        $xml .= "   <actes:Email>" . Helpers::escapeForXML($this->email) . "</actes:Email>\n";
        $xml .= "  </actes:Referent>\n";
        $xml .= " </actes:Emetteur>\n";

        if (strlen($this->return_mail) > 0) {
            $xml .= " <actes:AdressesRetour>\n";

            $emails = explode('|', $this->return_mail);
            $emails = array_unique($emails);
            foreach ($emails as $email) {
                $xml .= "  <actes:Email>" . $email . "</actes:Email>\n";
            }
            if (defined("ACTES_MAIL_BACKUP") && !empty(ACTES_MAIL_BACKUP)) {
                $xml .= "  <actes:Email>" . ACTES_MAIL_BACKUP . "</actes:Email>\n";
            }
            $xml .= " </actes:AdressesRetour>\n";
        } else {
            $this->errorMsg = "Informations manquantes pour générer l'enveloppe (pas de mail retour)";
            return false;
        }

        $xml .= " <actes:FormulairesEnvoyes>\n";


        foreach ($this->transactions as $transac) {
            $xml .= "  <actes:Formulaire>\n";
            $xml .= "   <actes:NomFichier>" . Helpers::escapeForXML(basename($transac->get("xmlFileName"))) . "</actes:NomFichier>\n";
            $xml .= "  </actes:Formulaire>\n";
        }


        $xml .= " </actes:FormulairesEnvoyes>\n";
        $xml .= "</actes:EnveloppeCLMISILL>\n";

        return $this->writeEnvFile($xml);
    }

  /**
   * \brief Méthode d'écriture du fichier enveloppe
   * \param $xml chaîne : contenu du fichier à écrire
   * \return
   */
    public function writeEnvFile($xml)
    {
        if (! Helpers::createDirTree(dirname($this->rootDir . '/' . $this->envXmlFile))) {
            $this->errorMsg = "Erreur système de fichiers.";
            return false;
        }

        if (! file_put_contents($this->rootDir . '/' . $this->envXmlFile, $xml)) {
            $this->errorMsg = "Erreur système de fichiers.";
            return false;
        }


        if (! $this->envXmlFileSize = @filesize($this->rootDir . '/' . $this->envXmlFile)) {
            $this->errorMsg = "Erreur système.";
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode de génération de l'archive finale .tar.gz
   * \return True en cas de succès, false sinon
   */
    public function generateArchiveFile()
    {
        if (isset($this->envXmlFile)) {
            if (empty($this->file_path)) {
                // Si le chemin n'est pas vide c'est que l'on est en mode import de .tar.gz
                $this->file_path = dirname($this->envXmlFile) . "/" . ACTES_APPLI_TRIGRAMME . '-' . preg_replace("/^(.*)\.([^.]+)$/", "\${1}", basename($this->envXmlFile)) . '.tar.gz';
            }


            if (count($this->transactions) > 0) {
              // En cas d'importation on remet dans l'archive tous les fichiers présents à l'origine
              // (qui se trouvent dans tmpDir)
                if (empty($this->tmpDir)) {
                    $this->buildFileList();
                }

                $filesDir = $this->rootDir . "/" . $this->destDir;



                $filesDir .= (empty($this->tmpDir)) ? "" : "/" . $this->tmpDir;

                if (! chdir($filesDir)) {
                    $this->errorMsg = "Erreur système de fichiers.";
                    return false;
                } else {
                    $cmd = 'tar cf - ';

                    if (empty($this->tmpDir)) {
                        foreach ($this->fileList as $file) {
                              $cmd .= " " . $file["name"];
                        }
                    } else {
                      // Mode import, on prend tous les fichiers du répertoire
                        $cmd .= " * ";
                    }

                    $cmd .= ' |gzip -9 > ' . escapeshellarg($this->rootDir . "/" . $this->file_path);


                    $status = system($cmd, $ret);

                    if ($status === false || $ret != 0) {
                        $this->errorMsg = "Erreur de création de l'archive. Retour " . $ret;
                        return false;
                    }

                    Helpers::fixPerms($this->rootDir . "/" . $this->file_path);
                }
            } else {
                $this->errorMsg = "Pas de message dans l'enveloppe.";
                return false;
            }
        } else {
            $this->errorMsg = "Fichier enveloppe introuvable.";
            return false;
        }

        if (! $this->file_size = filesize($this->rootDir . '/' . $this->file_path)) {
            $this->errorMsg = "Erreur système.";
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode de construction de la liste des fichiers contenu dans l'enveloppe
   */
    public function buildFileList()
    {
      // Fichier XML de l'enveloppe
        $this->fileList[] = array( "name" => basename($this->envXmlFile), "type" => "text/xml", "size" => filesize(ACTES_FILES_UPLOAD_ROOT . "/" . $this->envXmlFile));

        if (count($this->transactions) > 0) {
            foreach ($this->transactions as $transac) {
                // Fichier XML de l'acte
                $this->fileList[] = array( "name" => basename($transac->get("xmlFileName")), "type" => "text/xml", "size" => filesize(ACTES_FILES_UPLOAD_ROOT . "/" . $transac->get("xmlFileName")));

                // Fichier de l'acte (optionnel)
                if (isset($transac->files["acte"])) {
                    $this->fileList[] = array( "name" => basename($transac->files["acte"]["name"]), "type" => $transac->files["acte"]["mimetype"], "size" => $transac->files["acte"]["size"]);
                }

                // Pièces jointes (optionnelles)
                if (isset($transac->files["attachment"])) {
                    foreach ($transac->files["attachment"] as $attachment) {
                        $this->fileList[] = array( "name" => basename($attachment["name"]), "type" => $attachment["mimetype"], "size" => $attachment["size"]);
                    }
                }
            }
        }
    }

  /**
   * \brief Méthode d'importation d'une enveloppe .tar.gz déjà constituée
   * \param $name chaîne : Nom du fichier original de l'archive
   * \param $path chaîne : Chemin vers le fichier dans le système de fichier local
   * \return Un tableau des noms des fichiers métiers XML contenus dans l'enveloppe, false sinon
   */
    public function importArchiveFile($name, $path)
    {
        if (! isset($this->destDir)) {
            return false;
        }
        $dest = $this->rootDir . '/' . $this->destDir;

        $this->file_path = $this->destDir . "/" . $name;

        // Création du répertoire de stockage de l'archive
        if (! Helpers::createDirTree($dest)) {
            $this->errorMsg = "Erreur système de fichiers.";
            return false;
        }
        //Mise en place de l'archive à son emplacement définitif
        if (!move_uploaded_file_wrapper($path, $this->rootDir . '/' . $this->file_path)) {
            $this->errorMsg = "Erreur système. Abandon.";
            return false;
        }

        // Vérification du format de l'archive
        if (! $this->checkArchiveType($this->rootDir . '/' . $this->file_path)) {
            $this->errorMsg = "Mauvais format de l'archive (.tar.gz requis).";
            return false;
        }

        // Extraction de la taille de l'archive
        if (! $this->file_size = filesize($this->rootDir . '/' . $this->file_path)) {
            $this->errorMsg = "Erreur système.";
            return false;
        }

        // Vérification taille et antivirus sur l'archive
        if (! $this->checkArchiveConformity($this->rootDir . '/' . $this->file_path)) {
            return false;
        }

        $this->genTempDirectory();

  // Extraction de l'archive dans un répertoire temporaire
        if (! $this->extractArchive($this->rootDir . '/' . $this->destDir . "/" . $this->tmpDir)) {
            $this->errorMsg = "Erreur désarchivage archive. Abandon.";
            return false;
        }

  // Chargement du fichier XML enveloppe
  // Le nom du fichier xml enveloppe est le nom de l'archive moins le préfixe
  // et avec l'extension .xml à la place de .tar.gz
        $this->envXmlFile = preg_replace("/^[^\-]+-/", "", $name);
        $this->envXmlFile = $this->destDir . "/" . $this->tmpDir . "/" . preg_replace("/\.tar\.gz$/", ".xml", $this->envXmlFile);

        $xmlFile = $this->rootDir . "/" . $this->envXmlFile;

        if (! file_exists($xmlFile)) {
            $this->errorMsg = "Impossible de trouver le fichier XML enveloppe correspondant à l'archive.";
            return false;
        } else {
            if (! $this->envXmlFileSize = filesize($xmlFile)) {
                $this->errorMsg = "Erreur système.";
                return false;
            }
        }

        if (($xmlFiles = $this->processXMLEnvelope($xmlFile)) === false) {
            return false;
        }

  // Écriture de l'enveloppe modifiée (ajout mail retour TdT)
        if (! $this->writeEnvFile($this->envXmlObj->AsXML())) {
            return false;
        }

        return $xmlFiles;
    }

  /**
   * \brief Méthode de vérification du type de l'archive
   * \param $path chaîne : Chemin vers le fichier archive
   * \return True en cas de format .tar.gz, false sinon
   *
   */
    public function checkArchiveType($path)
    {
        if (! empty($path)) {
          // mime_content_type() ne semble pas fonctionner
          // On tente bêtement de lire l'archive et on récupère le statut de sortie

          // Test du format gzip
            $cmd = "gzip -d -c " . escapeshellarg($path);

            exec($cmd, $output, $ret);

            return $ret === 0;
        }

        return false;
    }

  /**
   * \brief Méthode de vérification de l'archive (taille et anti-virus)
   *        La vérification par l'anti-virus se fait après le postage de la transaction
   * \param $path chaîne (optionnel) : chemin vers l'archive à controler (file_path par défaut)
   * \return True en cas de succès, false si l'archive n'est pas valide
   */
    public function checkArchiveConformity($path = false)
    {
        return $this->checkArchiveSize($path);
      //&& $this->checkArchiveSanity($path)
    }

  /**
   * \brief Méthode de vérification de la taille de l'archive
   * \return True en cas de conformité, false sinon
   */
    public function checkArchiveSize()
    {
        if (isset($this->file_path) && ! empty($this->file_path)) {
            if ($this->file_size > ACTES_ARCHIVE_MAX_SIZE) {
                $this->errorMsg = "La taille de l'archive est trop élevée, " . $this->file_size . " octets (maximum " . ACTES_ARCHIVE_MAX_SIZE . " octets autorisés)";
                return false;
            } else {
                return true;
            }
        }
        $this->errorMsg = $this->file_path . " n'existe pas ou il est vide.";
        return false;
    }

  /**
   * \brief Méthode de génération d'un répertoire temporaire avec un nom aléatoire
  */
    private function genTempDirectory()
    {
        $this->tmpDir = Helpers::genTempName();
    }

  /**
   * \brief Méthode d'extraction d'une enveloppe archive .tar.gz
   * \param $dest chaîne : répertoire de destination de l'extraction
   * \return True en cas de succès, false sinon
   *
   * L'extraction est faite dans le répertoire tmpDir
   * en dessous du répertoire de l'archive.
   *
   */
    public function extractArchive($dest)
    {
        if (! Helpers::createDirTree($dest)) {
            $this->errorMsg = "Erreur système de fichiers.";
            return false;
        }

        $cmd = "gzip -d -c " . escapeshellarg($this->rootDir . '/' . $this->file_path)
            . " | tar xf - --directory " . escapeshellarg($dest)
            . " >/dev/null 2>&1";

        exec($cmd, $output, $ret);

        if ($ret != 0) {
            return false;
        } else {
            return true;
        }
    }

  /**
   * \brief Méthode d'importation de l'enveloppe XML existante
   * \return Tableau de nom de fichier XML des actes en cas de succès, false sinon
   *
   */
    public function processXMLEnvelope($xmlFile)
    {
        if (($this->envXmlObj = @simplexml_load_file($xmlFile)) === false) {
            $this->errorMsg = "Erreur lors de l'importation du fichier XML de l'enveloppe.";
            return false;
        }

      // Extraction des informations du XML pour initialiser l'enveloppe
        $namespaces = $this->envXmlObj->getDocNamespaces();
      // Récupération des éléments dans le namespace "actes"
        $actesItems = $this->envXmlObj->children($namespaces["actes"]);
        $this->name = Helpers::getFromXMLElt($actesItems->Emetteur->Referent->Nom);
        $this->telephone = Helpers::getFromXMLElt($actesItems->Emetteur->Referent->Telephone);
        $this->email = Helpers::getFromXMLElt($actesItems->Emetteur->Referent->Email);

      // Vérification SIREN enveloppe <=> posteur
        $authority_attr = $actesItems->Emetteur->IDCL->attributes($namespaces["insee"]);
        if (strcmp($this->siren, $authority_attr['SIREN']) != 0) {
            $this->errorMsg = "Le numéro de SIREN contenu dans l'enveloppe ne correspond pas à celui de l'utilisateur authentifié. Abandon.";
            return false;
        }

      // Vérification type de collectivité enveloppe <=> posteur
        $authority_attr = $actesItems->Emetteur->IDCL->attributes($namespaces["actes"]);
        if ($this->authority_type_code != $authority_attr['Nature']) {
            $this->errorMsg = "Le type de collectivité contenu dans l'enveloppe ne correspond pas à celui de l'utilisateur authentifié. Abandon.";
            return false;
        }

      // Vérification département enveloppe <=> posteur
        $department = Helpers::getFromXMLElt($authority_attr['Departement']);
        if (strcmp($this->department, $department) != 0) {
            $this->errorMsg = "Le département de la collectivité contenu dans l'enveloppe ne correspond pas à celui de l'utilisateur authentifié. Abandon.";
            return false;
        }

      // Vérification arrondissement enveloppe <=> posteur
        $district = Helpers::getFromXMLElt($authority_attr['Arrondissement']);
        if (strcmp($this->district, $district) != 0) {
            $this->errorMsg = "L'arrondissement de la collectivité contenu dans l'enveloppe ne correspond pas à celui de l'utilisateur authentifié. Abandon.";
            return false;
        }

      // Ajout adresse de retour du TdT
        if (($this->envXmlObj = $this->xmlAddTDTReturnMail()) === false) {
            return false;
        }

      // Rafraichissement des éléments Actes
        $namespaces = $this->envXmlObj->getDocNamespaces();
        $actesItems = $this->envXmlObj->children($namespaces["actes"]);

      // Récupération adresses de retour
        $return_mails = $actesItems->AdressesRetour;

        $mails = array();
        foreach ($return_mails->Email as $mail) {
            $mails[] = sprintf("%s", $mail);
        }

        $this->return_mail = implode('|', $mails);

      // Extraction des noms des fichiers XML décrivant les messages contenus dans l'enveloppe
        $xmlFiles = array();
        $messages = $actesItems->FormulairesEnvoyes;

        foreach ($messages->Formulaire as $message) {
            $xmlFiles[] = $this->destDir . "/" . $this->tmpDir . "/" . sprintf("%s", $message->NomFichier);
        }

        return $xmlFiles;
    }

  /**
   * \brief Méthode d'ajout de l'adresse mail de retour du TdT dans le fichier XML enveloppe
   * \return Un objet SimpleXMLElement en cas de succès, false sinon
   *
   */
    public function xmlAddTDTReturnMail()
    {
      // La version PHP 5.1.2 ne contient pas la méthode addChild sur un SimpleXMLElement
      // (apparition en 5.1.4).
      // Il faut donc passer par DOM pour modifier le fichier XML
        if (($domElt = @dom_import_simplexml($this->envXmlObj)) === false) {
            $this->errorMsg = "Erreur XML.";
            return false;
        }

        $dom = new DOMDocument('1.0', 'iso-8859-1');
        $domElt = $dom->importNode($domElt, true);
        $dom->appendChild($domElt);

      // TODO : voir comment récupérer l'URI du namespace depuis le fichier
        $newMail = new DOMElement("actes:Email", ACTES_TDT_MAIL_ADDRESS, "http://www.interieur.gouv.fr/ACTES#v1.1-20040216");

        $mailList = $dom->getElementsByTagName("AdressesRetour");
        if ($mailList->length <= 0) {
            $this->errorMsg = "L'enveloppe ne contient pas la balise AdressesRetour";
            return false;
        }
        $mailList->item(0)->appendChild($newMail);
        return simplexml_import_dom($dom);
    }

  /**
   * \brief Méthode de suppression des fichiers intermédiaires ayant servi à la contruction de l'archive
   * \return True en cas de succès, false sinon
  */
    public function purgeFiles()
    {
      // Suppression du fichier XML de l'enveloppe
        if (isset($this->envXmlFile) && !empty($this->envXmlFile)) {
            if (! @unlink($this->rootDir . '/' . $this->envXmlFile)) {
                $this->errorMsg .= "Ne peut supprimer un fichier temporaire.";
                return false;
            }
        }

      // Suppression des fichiers de chaque transaction
        foreach ($this->transactions as $trans) {
            /** @var ActesTransaction $trans */
            if (! $trans->purgeFiles()) {
                $this->errorMsg .= $trans->getErrorMsg();
                return false;
            }
        }

      // Suppression du répertoire temporaire s'il existe
        if (! empty($this->tmpDir)) {
            $cmd = "rm -rf " . escapeshellarg($this->rootDir . "/" . $this->destDir . "/" . $this->tmpDir);

            exec($cmd, $output, $ret);

            if ($ret != 0) {
                return false;
            }
        }

        return true;
    }

  /**
   * \brief Méthode de suppression du fichier archive .tar.gz
   * \return True en cas de succès, false sinon
  */
    public function deleteArchiveFile()
    {
        if (isset($this->file_path)) {
            if (! @unlink(ACTES_FILES_UPLOAD_ROOT . "/" . $this->file_path)) {
                $this->errorMsg .= "Erreur lors de la tentative de suppression du fichier archive.";
                return false;
            } else {
              // Tentative de suppression du répertoire contenant
                @rmdir(ACTES_FILES_UPLOAD_ROOT . "/" . dirname($this->file_path));
                return true;
            }
        }

        return false;
    }



  /**********************/
  /* Méthodes statiques */
  /**********************/

  /**
   * \brief Méthode qui renvoie le fichier archive .tar.gz au navigateur
   */
    public function sendFile()
    {
        if (! isset($this->file_path)) {
            return false;
        }

        $objectInstancier = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
        $actesRetriever = $objectInstancier->get(ActesRetriever::class);
        $archive_path = $actesRetriever->getPath($this->file_path);

        if (! file_exists($archive_path)) {
            $this->errorMsg = "Le fichier archive n'est pas/plus disponible.";
            return false;
        }

        if (
            ! Helpers::sendFileToBrowser(
                $archive_path,
                basename($archive_path),
                "application/x-gzip"
            )
        ) {
            $this->errorMsg = "Erreur envoi fichier";
            return false;
        }
        return true;
    }

  /**
   * \brief Méthode d'obtention du numéro de série suivant pour la génération du nom du fichier XML enveloppe
   * \param $authority Authority (optionnel) : objet représentant la collectivité demandeuse du numéro
   * \return Un numéro de série
  */
    public function getNextEnvelopeSerial($authority_id)
    {
        if (! $authority_id && ! isset($this->user_id)) {
            return false;
        }
        if (! $authority_id) {
            $user = new User($this->user_id);
            $user->init();
            $authority_id =  $user->get("authority_id");
        }


        if (! $this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

      // On vérifie si le numéro doit être remis à zéro ou pas
        $sql = "SELECT reset_date, serial FROM actes_envelope_serials WHERE authority_id=$authority_id FOR UPDATE";

        $result = $this->db->select($sql);

        if (! $result->isError()) {
            if ($result->num_row() <= 0) {
              // Pas encore d'entrée pour cette collectivité
                $date = date('Y-m-d');
                $sql = "INSERT INTO actes_envelope_serials (authority_id, reset_date, serial) VALUES($authority_id, '$date', 2)";

                if (! $this->db->exec($sql)) {
                    $this->errorMsg = "Erreur d'accès base de données.";
                    $this->db->rollback();
                    return false;
                } else {
                    $this->db->commit();
                    return 1;
                }
            } else {
                $row = $result->get_next_row();
                $date = substr($row["reset_date"], 0, 10);
                $today = date('Y-m-d');

                if (strcmp($today, $date) != 0) {
                  // La dernière remise à zéro n'est pas d'aujourd'hui => on remet à zéro le compteur
                    $sql = "UPDATE actes_envelope_serials SET reset_date='" . $today . "', serial=2 WHERE authority_id=$authority_id" ;

                    if (! $this->db->exec($sql)) {
                        $this->errorMsg = "Erreur d'accès base de données.";
                        $this->db->rollback();
                        return false;
                    } else {
                        $this->db->commit();
                        return 1;
                    }
                } else {
                  // Mise à jour aujourd'hui => on incrémente le numéro de série

                // pour le bug 240, modifié par HTan, 15-12-2008
                    if ($row['serial'] >= 10000 || $row['serial'] <= 0) {
                        $this->errorMsg = "Serial number: déborder 10000. Vérifier serial est modifié par quelqu'un ou on a commité plus que 9999 tranactions par jour.";
                        return false;
                    }
                  //-----

                    $sql = "UPDATE actes_envelope_serials SET serial=serial+1 WHERE authority_id=$authority_id";

                    if (! $this->db->exec($sql)) {
                        $this->errorMsg = "Erreur d'accès base de données.";
                        $this->db->rollback();
                        return false;
                    } else {
                        $this->db->commit();
                        return $row["serial"];
                    }
                }
            }
        } else {
            $this->errorMsg = "Erreur d'accès base de données.";
            return false;
        }
    }

  /**
   * \brief Méthode d'enregistrement d'une enveloppe dans la base de données
   * \param $validate booléen (optionnel) Demande la validation ou non des données de l'entité avant enregistrement (true par défaut)
   * \return true si succès, false sinon
   */
    public function save($validate = true, $bouchon_4_strict_standard = true)
    {
        $new = false;
        if ($this->isNew()) {
            $new = true;
            $this->submission_date = date('Y-m-d H:i:s');
        }

        $saveSQLRequest = parent::buildSaveSQLRequest($validate);
        if (!$saveSQLRequest->isValid()) {
            return false;
        }


        if (! $this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

        if (! $this->db->exec($saveSQLRequest->getRequest(), $saveSQLRequest->getParams())) {
            $this->errorMsg = "Erreur lors de la sauvegarde de la transaction.";
            $this->db->rollback();
            return false;
        }

        if ($new) {
          // Ajout du fichier enveloppe dans la liste des fichiers inclus
            $sql = "INSERT INTO actes_included_files (envelope_id, filename, filetype, filesize) VALUES(?, ?, 'text/xml',?)";

            if (! $this->db->exec($sql, [$this->id,basename($this->envXmlFile),$this->envXmlFileSize])) {
                $this->errorMsg = "Erreur lors de la journalisation des fichiers contenus dans l'archive.";
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
   * \brief Méthode de suppression d'une enveloppe de la base de données
   * \param $id integer (optionnel) Numéro d'identifiant de l'enveloppe, si non spécifié, entité en cours
   * \return true si succès, false sinon
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

        if (! $this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

        $sql = "DELETE FROM actes_included_files WHERE envelope_id=" . $id;

        if (! $this->db->exec($sql)) {
            $this->errorMsg = "Erreur lors de la suppression des fichiers reliés à l'enveloppe.";
            $this->db->rollback();
            return false;
        }

      // On ne supprime pas les transaction étant donné que l'appel à cette méthode se fait
      // uniquement quand l'enregistrment d'une transaction échoue

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
   * \brief Méthode d'obtention de la liste des enveloppes et tous leurs attributs
   * \param $cond (optionnel) chaîne : Chaîne contenant les conditions (SQL) à appliquer à la fin de la requête BDD
   * \return Tableau des enveloppes
   */
    public function getEnvelopesList($cond = "")
    {
        if (
            ! $this->pagerInit('DISTINCT actes_envelopes.id, actes_envelopes.user_id, actes_envelopes.siren, ' .
            'actes_envelopes.submission_date, actes_envelopes.department, actes_envelopes.district, ' .
            'actes_envelopes.authority_type_code, actes_envelopes.name, actes_envelopes.telephone, ' .
            'actes_envelopes.email, actes_envelopes.file_path, actes_envelopes.return_mail, ' .
            'actes_envelopes.file_size, users.name, users.givenname ', 'actes_envelopes LEFT JOIN actes_transactions ON ' .
                    'actes_envelopes.id=actes_transactions.envelope_id LEFT JOIN users ON actes_envelopes.user_id=users.id', $cond)
        ) {
            return false;
        }

        return $this->data;
    }
}
