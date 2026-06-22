<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\UserSQL;

class Log extends DataObject
{
    protected $objectName = "logs";
    protected $prettyName = "Entrée de journal";

    protected $date;
    protected $module;
    protected $severity;
    protected $issuer;
    protected $user_id;
    protected $visibility;
    protected $message;
    protected $authority_id;
    protected $authority_group_id;

    protected $dbFields = array( "date" => array( "descr" => "Date", "type" => "isDate", "mandatory" => true),
                         "module" => array( "descr" => "Module", "type" => "isString", "mandatory" => false),
                         "severity" => array( "descr" => "Sévérité", "type" => "isString", "mandatory" => true),
                         "issuer" => array( "descr" => "Émetteur de l'entrée", "type" => "isString", "mandatory" => false),
                         "user_id" => array( "descr" => "Identifiant de l'utilisateur", "type" => "isInt", "mandatory" => false),
                         "visibility" => array( "descr" => "Visibilité", "type" => "isString", "mandatory" => false),
                         "message" => array( "descr" => "Message", "type" => "isString", "mandatory" => true),
                            "authority_id" => array("descr" => "Authority","type" => "isInt","mandatory" => false),
                            "authority_group_id" => array("descr" => "Authority group","type" => "isInt","mandatory" => false)
                         );

    protected $severities = array( 0 => "DEBUG",
                           1 => "INFO",
                           2 => "WARNING",
                           3 => "ERROR",
                           4 => "CRITICAL"
                           );

  /**
   * \brief Constructeur d'une entrée de log
   * \param id integer Numéro d'id d'une collectivité existante avec lequel initialiser l'objet
   */
    public function __construct($id = false)
    {
        parent::__construct($id);
        if ($id) {
            $this->init();
        }
    }

  /**
   * \brief Constructeur d'une entrée de log à partir d'infos fournies en paramètres
   * \param $issuer chaîne : Créateur de l'entrée de journal
   * \param $message chaîne : Message de l'entrée de journal
   * \param $severity chaîne : Sévérité du message
   * \param $date chaîne (optionnel) : Date de l'entrée de journal (date courante par défaut)
   * \param $visibility chaîne (optionnel) : Visibilité de l'entrée de log ('USER', 'ADM' ou 'SADM') (vide par défaut)
   * \param $module chaîne (optionnel) : Module concerné par le message (vide par défaut)
   * \param $user objet User (optionnel) : Utilisateur concerné par l'entrée de journal (vide par défaut)
   * \return True en cas de succès, false sinon
   */
    public static function newEntry($issuer, $message, $severity, $date = false, $visibility = false, $module = false, $user = false, $userid = false)
    {
        $logEntry = new Log();
        $logEntry->set("issuer", $issuer);
        $logEntry->set("message", $message);
        $logEntry->set("severity", $severity);
        if (! $date) {
            $date = date('Y-m-d H:i:s');
        }

        $logEntry->set("date", $date);

        if ($module) {
            $logEntry->set("module", $module);
        }


        if ($user) {
            /** @var User $user */
            $userid = $user->getId();
        }

        if ($userid) {
            $logEntry->set("user_id", $userid);
        }
        if ($visibility) {
            $logEntry->set("visibility", $visibility);
        }


        $sqlQuery = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier()->get(SQLQuery::class);

        $authority_id = false;
        $authority_group_id = false;
        if ($userid) {
            $userSQL = new UserSQL($sqlQuery);
            $info = $userSQL->getInfo($userid);
            if ($info) {
                $authority_id = $info['authority_id'];
            }
        }
        if ($authority_id) {
            $authoritySQL = new AuthoritySQL($sqlQuery);
            $info = $authoritySQL->getInfo($authority_id);
            if ($info) {
                $authority_group_id = $info['authority_group_id'];
            }
        }

        $logEntry->set("authority_id", $authority_id);
        $logEntry->set("authority_group_id", $authority_group_id);

      // Enregistrement de l'entrée pour déterminer son id
        if (! $logEntry->save()) {
            return false;
        }

        return true;
    }
}
