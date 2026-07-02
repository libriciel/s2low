<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Helpers;

/**
 * \class ActesClassification ActesClassification.class.php
 * \brief Cette classe permet de gérer les classifications matières/sous-matières
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 03.08.2006
 *
 *
 * Cette classe fournit des méthodes de gestion de la liste
 * de classification matères/sous-matières
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */
class ActesClassification extends DataObject
{
    protected $objectName = "actes_classification_requests";

    protected $request_date;
    protected $requested_by;
    protected $version_date;
    protected $xml_data;

    protected $dbFields = array(
        "request_date" => array("descr" => "Date de la demande", "type" => "isDate", "mandatory" => true),
        "requested_by" => array("descr" => "Identifiant du demandeur", "type" => "isInt", "mandatory" => true),
        "version_date" => array(
            "descr" => "Date de la version de la classification",
            "type" => "isDate",
            "mandatory" => false
        ),
        "xml_data" => array("descr" => "Fichier XML de la classification", "type" => "isString", "mandatory" => false)
    );

    /**
     * \brief Constructeur d'une requete de classification
     * \param id integer Numéro d'identifiant d'une requete existante avec laquelle initialiser l'objet
     */
    public function __construct($id = false)
    {
        parent::__construct($id);
    }

    /**
     * \brief Méthode d'initialisation avec les données de la dernière requête en date ayant réussie
     * \param $authority_id entier : Identifiant de la collectivité concernée
     * \return True en cas de succès, false sinon
     */
    public function initWithLastSuccessful($authority_id)
    {
        if (is_numeric($authority_id)) {
            $sql = "SELECT acr.id, acr.request_date, acr.requested_by, acr.version_date, acr.xml_data " .
                " FROM actes_classification_requests acr " .
                " LEFT JOIN users ON acr.requested_by=users.id " .
                " WHERE users.authority_id=? AND acr.xml_data IS NOT NULL " .
                " ORDER BY request_date DESC, version_date DESC LIMIT 1";

            $row = $this->db->getConnection()->fetchAssociative($sql, [$authority_id]);
            if (!$row) {
                return false;
            }
            
            $xml_data = $row['xml_data'];
            if (is_resource($xml_data)) {
                $contents = stream_get_contents($xml_data);
                fclose($xml_data);
            } else {
                $contents = $xml_data;
            }
            
            if ($contents === false) {
                return false;
            }
            
            $this->request_date = $row['request_date'];
            $this->requested_by = $row['requested_by'];
            $this->version_date = $row['version_date'];
            $this->xml_data = $contents;

            return true;
        }

        return false;
    }

    /**
     * \brief Méthode d'envoi de la classification
     * \return True en cas de succès, false sinon
     */
    public function pushXMLData()
    {
        if (!empty($this->xml_data)) {
            header_wrapper("Content-type: text/xml;charset=iso-8859-1");
            header_wrapper('Content-disposition: attachment; filename="classification.xml"');
            // Celles-ci pour IE
            header_wrapper("Expires: 0");
            header_wrapper("Cache-Control: must-revalidate, post-check=0,pre-check=0");
            header_wrapper("Pragma: public");

            echo $this->xml_data . "\r\n";
        } else {
            return false;
        }

        return true;
    }


    /**********************/
    /* Méthodes statiques */
    /**********************/

    /**
     * \brief Méthode de vérification qu'une transaction de demande de classification n'existe pas déjà dans la journée en cours
     * \param $authority_id entier : Identifiant de la collectivité concernée
     * \return True si une transaction a déjà été faites dans la journée en cours, false sinon
     */
    public static function hasTodayRequest($authority_id)
    {
        if (is_numeric($authority_id)) {
            $today = date('Y-m-d');
            $sql = "SELECT DISTINCT at.id FROM actes_transactions at"
                . " LEFT JOIN actes_transactions_workflow atw ON at.id=atw.transaction_id"
                . " LEFT JOIN actes_envelopes ae ON at.envelope_id=ae.id"
                . " LEFT JOIN users ON ae.user_id=users.id"
                . " WHERE at.type='7' "
                // On ne tient compte que des demandes dans les états posté, en attente ou transmis (3), de ce fait si une demande tombe en erreur
                // il sera possible de refaire une requête dans la même journée

                //Correger pour la report du bug: 190 =>lenteur du plateforme.
                . " AND (SELECT status_id FROM actes_transactions_workflow atw2 WHERE date = ( SELECT MAX(date) FROM actes_transactions_workflow atw3 WHERE atw3.transaction_id = atw2.transaction_id) AND transaction_id = at.id ORDER BY atw2.id DESC LIMIT 1) IN (1,2,3)"
                . " AND atw.date >= '" . $today . "' AND users.authority_id=" . $authority_id;

            $db = DatabasePool::getInstance();

            $result = $db->select($sql);

            if (!$result->isError() && $result->num_row() > 0) {
                return true;
            }
        }

        return false;
    }


    /**
     * \brief Méthode d'obtention de la date de la dernière classification utilisée
     * \param $authority_id entier : Identifiant de la collectivité concernée
     * \param $return_ansi bolléen : si true (défaut) retourne la date au format YYYY-MM-DD, format BDD sinon
     * \return La date de la dernière classification utilisée
     */
    public static function getLastRevisionDate($authority_id, $return_ansi = true)
    {
        if (is_numeric($authority_id)) {
            // TODO : voir s'il ne vaut mieux pas ordonner par ID et prendre la date de la dernière requête
            $sql = "SELECT MAX(version_date) AS max_date FROM actes_classification_requests"
                . " LEFT JOIN users ON actes_classification_requests.requested_by=users.id"
                . " WHERE users.authority_id=" . $authority_id;

            $db = DatabasePool::getInstance();

            $result = $db->select($sql);

            if (!$result->isError() && $result->num_row() > 0) {
                $row = $result->get_next_row();
                if ($return_ansi) {
                    return Helpers::getANSIDateFromBDDDate($row["max_date"]);
                } else {
                    return $row["max_date"];
                }
            }
        }

        return null;
    }

    /**
     * \brief Méthode de récupération de la liste de classification
     * \param $authority_id entier : Identifiant de la collectivité concernée
     * \return Un tableau contenant la liste de classification matières/sous-matières
     */
    public static function getClassificationList($authority_id)
    {
        if (is_numeric($authority_id)) {
            $sql = "SELECT id, level, code, parent_id, description FROM actes_classification_codes"
                . " WHERE authority_id=" . $authority_id . " ORDER BY level,code ASC";

            $db = DatabasePool::getInstance();

            $result = $db->select($sql);


            $classif = array();

            if (!$result->isError()) {
                while ($row = $result->get_next_row()) {
                    if (!isset($classif[$row["id"]])) {
                        $classif[$row["id"]] = array();
                    }

                    $classif[$row["id"]]["id"] = $row["id"];
                    $classif[$row["id"]]["level"] = $row["level"];
                    $classif[$row["id"]]["code"] = $row["code"];
                    $classif[$row["id"]]["description"] = $row["description"];

                    if (isset($row["parent_id"]) && !empty($row["parent_id"])) {
                        $classif[$row["id"]]["parent_id"] = $row["parent_id"];

                        if (!isset($classif[$row["parent_id"]])) {
                            $classif[$row["parent_id"]] = array();
                        }

                        $classif[$row["parent_id"]]["children_id"][] = $row["id"];
                    }
                }
            } else {
                return false;
            }

            return $classif;
        }

        return false;
    }

    /**
     * \brief Méthode de récupération du fichier XML de la classification
     * \return Une chaîne contenant les données XML de la classification
     */
    public static function getClassificationXMLData($authority_id)
    {
    }
}
