<?php

/**
 * \class mail_annuaire  mail_annuaire.class.php
 * \brief Cette classe permet de modeliser le tableau correspond de mail_annuraire
 *
 * \author TH ,JMontiel
 * \date :23-04-2008
 *
 *
 * Cette classe fournit des méthodes de traiter le tableau mail_annuaire
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once(SITEROOT . "/class/DataObject.class.php");
class mail_annuaire extends DataObject
{
    protected $objectName = "mail_annuaire";
    protected $id;
    protected $user_id;
    protected $mail_address;
    protected $description;

    protected $dbFields =  array(
    "authority_id"          => array( "descr" => "Identifiant mail", "type" => "isInt", "mandatory" => true),
    "mail_address"  => array("descr" => "---", "type" => "isString", "mandatory" => true),
    "description"   => array("descr" => "---", "type" => "isString", "mandatory" => true),
    );

    function __construct($id = false)
    {
        parent::__construct($id);
    }

    public function newSave($address, $authority_id, $description)
    {
        $this->mail_address = $address;
        $this->authority_id = $authority_id;
        $this->description = $description;
        parent::save(false);
        return true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthority_id()
    {
        return $this->authority_id;
    }

    public function getMailAddress()
    {
        return $this->mail_address;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getMail()
    {
        return $this->mail_address . "-" . $this->description;
    }
}
