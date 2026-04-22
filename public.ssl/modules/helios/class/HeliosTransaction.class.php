<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Helpers;

/**
 * \class HeliosTransaction HeliosTransaction.class.php
 * \brief Cette classe permet de gérerer les transactions HELIOS
 * \author Cristina Pop <cpop@alternancesoft.com> et Jérômee Schell <j.schell@alternancesoft.com>
 * \date 20.02.2006
 *
 *
 * Cette classe fournit des méthodes de gestion des transactions
 * Helios
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

//deschide baza de date si recupeezaza toate tranzactiile userului
//dat
//apelata metoda sa din index.php

class HeliosTransaction extends DataObject
{
    protected $objectName = "helios_transactions";

    protected $id;
    protected $user_id;
    protected $authority_id;
    protected $filename;
    protected $file_size;
    protected $submission_date;
    protected $sha1;
    protected $siren;
    protected $acquit_filename;
    protected $archive_url;
    protected $last_status_id;
    protected $xml_nomfic;
    protected $xml_cod_col;
    protected $xml_cod_bud;
    protected $xml_id_post;
    protected $sae_transfer_identifier;

    public $allStatus = array ();

    public $files = array ();
    private $fileNameSerial;

    protected $related_transaction;

    protected $last_classification_date;

    protected $xmlFileName;
    protected $xmlFilesize;
    protected $xmlObj;

    private $workflow = array ();

    protected $destDir;

    protected $dbFields = [
        //"id" => array( "descr" => "Identifiant de la transaction", "type" => "isInt", "mandatory" => true),
        "user_id" => [
            "descr" => "Identifiant de l'utilisateur qui a créé la transaction",
            "type" => "isString",
            "mandatory" => true,
        ],
        "authority_id" => [
            "descr" => "Identifiant de la collectivité",
            "type" => "isString",
            "mandatory" => true,
        ],
        "filename" => [
            "descr" => "Nom du fichier posté",
            "type" => "isString",
            "mandatory" => true,
        ],
        "file_size" => [
            "descr" => "taille du fichier posté",
            "type" => "isInt",
            "mandatory" => true,
        ],
        "submission_date" => [
            "descr" => "date de la postulation",
            "type" => "isDate",
            "mandatory" => true,
        ],
        "sha1" => [
            "descr" => "le code sha1 calculer par le contenu du fichier",
            "type" => "isString",
            "mandatory" => true,
        ],
        "siren" => [
            "descr" => "siren de son propre collectivite",
            "type" => "isString",
            "mandatory" => true,
        ],
        "acquit_filename" => [
            "descr" => "Nom du fichier d'acquittement",
            "type" => "isString",
            "mandatory" => false,
        ],
        "archive_url" => [
            "descr" => "",
            "type" => "isString",
            "mandatory" => false,
        ],
        "last_status_id" => [
            "descr" => "",
            "type" => "isInt",
            "mandatory" => false,
        ],
        "xml_nomfic" => [
            "descr" => "",
            "type" => "isString",
            "mandatory" => false,
        ],
        "xml_cod_col" => [
            "descr" => "",
            "type" => "isString",
            "mandatory" => false,
        ],
        "xml_cod_bud" => [
            "descr" => "",
            "type" => "isString",
            "mandatory" => false,
        ],
        "xml_id_post" => [
            "descr" => "",
            "type" => "isString",
            "mandatory" => false,
        ],
        "sae_transfer_identifier" => [
            "descr" => "",
            "type" => "isString",
            "maxlength" => 256,
            "mandatory" => false,
        ],
    ];

  /**
   * \brief Constructeur d'une transaction
   * \param id integer Numéro d'identifiant d'une transaction existante avec laquelle initialiser l'objet
   */
    public function __construct($id = false)
    {
        parent :: __construct($id);

        $this->fileNameSerial = 1;
    }

  /**
   * \brief Méthode initialisant l'entité avec l'identifiant courant
   * \return true si succés, false sinon
  */
    public function init()
    {
        if (!parent :: init()) {
            return false;
        }
        return true;
    }

    /**
   * \brief Méthode permettant de fixer la valeur d'un attribut
   * \param $name chaîne : Nom de l'attribut
   * \param $val : valeur de l'attribut
  */
  //cred ca fixeaza valori pentru atributele clasei...!

    public function set($name, $val)
    {
        switch ($name) {
            case "user_id":
            case "filename":
            case "signed":
        }
        parent :: set($name, $val);
    }

  /*
   * \brief Méthode d'obtention du nom d efichier qui correponde à une transaction
   * \param $transaction_id
   * \return filename
   *
   */
  //de testat
    public function getFilenameForId($id)
    {
        $sql = "SELECT filename FROM helios_transactions" . " WHERE id=" . $id;

        $db = DatabasePool :: getInstance();
        $result = $db->select($sql);

        if (!$result->isError()) {
            $row = $result->get_next_row();
            return $row["filename"];
        } else {
            return false;
        }
    }
    public function getSha1ForId($id)
    {
        $sql = "SELECT sha1 FROM helios_transactions" . " WHERE id=" . $id;

        $db = DatabasePool :: getInstance();
        $result = $db->select($sql);

        if (!$result->isError()) {
            $row = $result->get_next_row();
            return $row["sha1"];
        } else {
            return false;
        }
    }

    public function getAcquitFilenameForId($id)
    {
        $sql = "SELECT acquit_filename FROM helios_transactions" . " WHERE id=?";

        $db = DatabasePool :: getInstance();
        $result = $db->select($sql, [$id]);

        if (!$result->isError()) {
            $row = $result->get_next_row();
            return $row["acquit_filename"];
        } else {
            return false;
        }
    }

  /**
   * \brief Méthode d'obtention de l'état courant d'un transaction
   * \return L'identifiant de l'état courant de la transaction
   */
    public function getCurrentStatus()
    {
        if (isset($this->id) && !empty($this->id)) {
            $sql = "SELECT status_id, message FROM helios_transactions_workflow atw WHERE date = ( SELECT MAX(date) FROM helios_transactions_workflow atw2 WHERE atw2.transaction_id = atw.transaction_id) AND transaction_id = ? ORDER BY atw.id DESC LIMIT 1";

            $result = $this->db->select($sql, [ $this->id ]);

            if (!$result->isError()) {
                $row = $result->get_next_row();
                return $row["status_id"];
            }

            return false;
        }
    }

 /**
  * \brief Méthode de récupération du cycle de vie de cette transaction
  * \return Un tableau contenant le workflow de la transaction
  */
    public function fetchWorkflow()
    {
        if (isset($this->id)) { // && count($this->workflow) <= 0) {
            $sql = "SELECT id, status_id, date, message FROM helios_transactions_workflow WHERE transaction_id=" . $this->id . " ORDER BY date, id ASC";

            $result = $this->db->select($sql);

            if (!$result->isError()) {
                $this->workflow = $result->get_all_rows();
            }
        }

        return $this->workflow;
    }

  /**
   *  \brief Méthhode d'obtention de l'id de l'user qui correponde à une transaction
   * \param $transaction_id
   * \return user name
   * //de testat
   */
    public function getUserForId($id)
    {
        $sql = "SELECT user_id FROM helios_transactions" . " WHERE id=" . $id;

        $db = DatabasePool :: getInstance();
        $result = $db->select($sql);

        if (!$result->isError()) {
            $row = $result->get_next_row();
            return $row["user_id"];
        } else {
            return false;
        }
    }


    public function sendAcquit($filename)
    {

        if (!file_exists(HELIOS_RESPONSES_ROOT . $filename) || $filename == null) {
            $this->errorMsg = "Le fichier '" . $filename . "' n'est pas/plus disponible.";
            echo "<br>helios Tansaction_class: sendAcquit " . $this->errorMsg;
            return false;
        }

        $ret_value = true;
      //AICI pot incerca sa modific parametrii...
        if (!\S2lowLegacy\Class\Helpers\ResponseHelper::sendFileToBrowser(HELIOS_RESPONSES_ROOT . $filename, $filename, "text/xml")) {
            $this->errorMsg = "Erreur envoi fichier";
            echo "<br> heliosTansaction_class: sendAcquit " . $this->errorMsg;
            $ret_value = false;
        }

        return $ret_value;
    }

  /**
   * \brief Méthode d'obtention de la liste des statuts des transactions
   * \return Tableau des statuts de transactions
   */
    public static function getStatusList()
    {
        $sql = "SELECT id, name FROM helios_status";

        $db = DatabasePool :: getInstance();

        $result = $db->select($sql);
        $types = array ();

        if (!$result->isError()) {
            while ($row = $result->get_next_row()) {
                $types[$row["id"]] = $row["name"];
            }
        }
        return $types;
    }

 /**
  *  \brief get list de transaction
  *  \param $authority_id: optional , pour spécialiser la collectivité.
  *  \reuturn table de transactions
  *
  */
    public static function getTransationHistory($authority_id = false)
    {
        $sql = "SELECT DISTINCT ht.id, ht.filename, ht.sha1, ht.file_size, atw.date, auth.siren, auth.department, auth.district";
        $sql .= " FROM helios_transactions ht,authorities auth, users, helios_transactions_workflow atw";
        $sql .= " WHERE ht.user_id=users.id AND users.authority_id=auth.id AND atw.transaction_id=ht.id";
        // On veut récupérer la date à la transaction a ététransmise => statut 3
        $sql .= " AND atw.status_id=3";

        if ($authority_id) {
            $sql .= " AND auth.id=" . $authority_id;
        }

        $sql .= " ORDER BY atw.date DESC";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        $trans = array();

        if (! $result->isError()) {
            while ($row = $result->get_next_row()) {
                $trans[] = $row;
            }
        }
        return $trans;
    }
}
