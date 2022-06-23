<?php

/**
 * \class mail_annuaire  mail_included_file.class.php
 * \brief Cette classe permet de modeliser le tableau correspond de mail_included_file
 *
 * \author TH ,JMontiel
 * \date :23-04-2008
 *
 *
 * Cette classe fournit des méthodes de traiter le tableau mail_included_file
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once(SITEROOT . "/class/DataObject.class.php");
require_once(SITEROOT . "/class/FileUploader.class.php");


class mail_included_file extends DataObject
{
    private $lastError;

    protected $objectName = "mail_included_file";
    protected $mail_transaction_id;
    protected $filename;
    protected $filetype;
    protected $filesize;
    protected $dbFields =  array(
    "mail_transaction_id"      => array( "descr" => "Identifiant utilisateur", "type" => "isInt", "mandatory" => true),
    "filename"       => array("descr" => "---", "type" => "isString", "mandatory" => true),
    "filetype"       => array("descr" => "---", "type" => "isString", "mandatory" => true),
    "filesize"       => array("descr" => "---", "type" => "isString", "mandatory" => true),
    );
    function __construct($id = false)
    {
        parent::__construct($id);
    }

    function getLastError()
    {
        return $this->lastError;
    }

    function newSave($file_name, $Transaction_id, $newdir)
    {

        $uploader = new FileUploader();
        $uploader->setDestinationDirectory($newdir);
        $uploader->disableForbidenExtension();

        $resultUpload = $uploader->upload($file_name);

        if ($resultUpload == false) {
            $this->lastError = $uploader->getLastError();
            return false;
        }
        $this->set("filename", $uploader->getFileName());
        $this->set("filetype", $uploader->getExtension());
        $this->set("filesize", $uploader->getFileSize());

        $this->set("mail_transaction_id", $Transaction_id);
        parent::save(false);
        return true;
    }

    function getMailTransactionId()
    {
        return $this->mail_transaction_id;
    }
    function getFileName()
    {
        return $this->filename;
    }
    function getFileType()
    {
        return $this->filetype;
    }
    function getFileSize()
    {
        return $this->filesize;
    }
}
