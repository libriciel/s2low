<?php

/**
 * \class MailErrors  MailErrors.class.php
 * \brief Cette classe permet de modeliser le tableau correspond de MailErrors
 *
 * \author TH  ,JMontiel
 * \date :23-04-2008
 *
 *
 * Cette classe fournit des méthodes de traiter le tableau MailErrors
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once(SITEROOT . "/class/DataObject.class.php");
class MailErrors extends DataObject
{
    protected $objectName = "mail_errors";
    protected $id;
    protected $mail_message_emis_id;
    protected $date_registered;
    protected $message_retour;
    protected $dbFields =  array(
    "mail_message_emis_id"      => array( "descr" => "Identifiant mail", "type" => "isString", "mandatory" => true),
    "message_retour"       => array("descr" => "---", "type" => "isString", "mandatory" => true),
    "date_registered"       => array("descr" => "---", "type" => "isString", "mandatory" => true),
    );

    public function __construct($id = false)
    {
        parent::__construct($id);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMailMessageEmis_id()
    {
        return $this->mail_message_emis_id;
    }

    public function getMessage_retour()
    {
        return $this->message_retour;
    }
}
