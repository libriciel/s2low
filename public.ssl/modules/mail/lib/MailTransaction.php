<?php

namespace S2lowLegacy\Mail;

use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Mail\MailMessageEmis;

class MailTransaction extends DataObject
{
    public const STATUS_NO_CONFIRMATION = "aucune confirmation";
    public const STATUS_CONFIRMER_PARTIELLEMENT = "confirmé partiellement";
    public const STATUS_CONFIRMER = "confirmé";

    public static function getTabStatus()
    {
        return array(
            0 => '',
            self::STATUS_CONFIRMER,
            self::STATUS_NO_CONFIRMATION,
            self::STATUS_CONFIRMER_PARTIELLEMENT
        );
    }

    private $arrayEmail;

    protected $objectName = "mail_transaction";
    protected $user_id;
    protected $objet;
    protected $password;
    protected $message;
    protected $fn_download;
    protected $status;
    protected $date_envoi;
    protected $dbFields = array(
        "user_id" => array("descr" => "Identifiant utilisateur", "type" => "isInt", "mandatory" => true),
        "objet" => array("descr" => "---", "type" => "isString", "mandatory" => true),
        "password" => array("descr" => "---", "type" => "isString", "mandatory" => true),
        "message" => array("descr" => "---", "type" => "isString", "mandatory" => true),
        "fn_download" => array("descr" => "---", "type" => "isString", "mandatory" => true),
        "status" => array("descr" => "---", "type" => "isString", "mandatory" => true),
        "date_envoi" => array("descr" => "---", "type" => "isString", "mandatory" => true),
    );

    public function newSave($id)
    {
        $this->set("user_id", $id);
        $this->set("objet", \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("objet"));
        $this->set("password", \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("psw1"));
        $this->set("message", \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("message"));
        $this->set("status", self::STATUS_NO_CONFIRMATION);
        $now = date("Y-m-d H:i:s");
        $this->set("date_envoi", $now);
        parent::save(false);
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function getObjet()
    {
        return $this->objet;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getFNDownload()
    {
        return $this->fn_download;
    }

    public function getStatus()
    {
        return $this->status;
    }

    //TODO : Orthographe défaillante (utilisé partout)...
    public function getDateEvnoi()
    {
        return $this->date_envoi;
    }

    public function getFile($fileId)
    {
        $sql = "SELECT * FROM mail_included_file WHERE id=? AND mail_transaction_id= ?";
        $result = $this->db->select($sql, [$fileId, $this->getId()]);
        return $result->get_next_row();
    }

    public function isPasswordOK($password)
    {
        if (!$this->getPassword()) {
            return true;
        }
        return $password == $this->getPassword();
    }

    public function updateStatus()
    {
        $sql = "SELECT bool_and(ack) FROM mail_message_emis WHERE mail_transaction_id =? GROUP BY mail_transaction_id";

        $all_confirme = $this->db->getOneValue($sql, [$this->getId()]);

        $this->set("status", $all_confirme == 't' ? self::STATUS_CONFIRMER : self::STATUS_CONFIRMER_PARTIELLEMENT);
        $this->save(false);
    }

    public function getEmailByType($type)
    {
        $arrayEmail = $this->getArrayEmail();
        return implode(',', $arrayEmail[$type]);
    }

    private function getArrayEmail()
    {
        if ($this->arrayEmail) {
            return $this->arrayEmail;
        }

        $this->arrayEmail = array(
            MailMessageEmis::TYPE_MAIL_TO => array(),
            MailMessageEmis::TYPE_MAIL_CC => array(),
            MailMessageEmis::TYPE_MAIL_BCC => array(),
        );

        $sql = "SELECT * FROM mail_message_emis WHERE mail_transaction_id =  ?";
        $result = $this->db->select($sql, [$this->getId()]);

        while ($info = $result->get_next_row()) {
            $this->arrayEmail[$info['type_envoi']][] = $info['email'];
        }
        return $this->arrayEmail;
    }
}
