<?php

namespace S2lowLegacy\Mail;

use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\FileUploader;

/**
 * \class MailAnnuaire  MailIncludedFile.class.php
 * \brief Cette classe permet de modeliser le tableau correspond de MailIncludedFile
 *
 * \author TH ,JMontiel
 * \date :23-04-2008
 *
 *
 * Cette classe fournit des méthodes de traiter le tableau MailIncludedFile
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */
class MailIncludedFile extends DataObject
{
    private $lastError;

    protected $objectName = "mail_included_file";
    protected $mail_transaction_id;
    protected $filename;
    protected $filetype;
    protected $filesize;
    protected $dbFields = array(
        "mail_transaction_id" => array("descr" => "Identifiant utilisateur", "type" => "isInt", "mandatory" => true),
        "filename" => array("descr" => "---", "type" => "isString", "mandatory" => true),
        "filetype" => array("descr" => "---", "type" => "isString", "mandatory" => true),
        "filesize" => array("descr" => "---", "type" => "isString", "mandatory" => true),
    );

    public function __construct($id = false)
    {
        parent::__construct($id);
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function newSave($file_name, $Transaction_id, $newdir)
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

    public function getMailTransactionId()
    {
        return $this->mail_transaction_id;
    }

    public function getFileName()
    {
        return $this->filename;
    }

    public function getFileType()
    {
        return $this->filetype;
    }

    public function getFileSize()
    {
        return $this->filesize;
    }
}
