<?php

namespace S2lowLegacy\Mail;

use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Mail\MailTransaction;

class MailMessageEmis extends DataObject
{
    public const TYPE_MAIL_TO = "mailTo";
    public const TYPE_MAIL_CC = "mailCC";
    public const TYPE_MAIL_BCC = "mailBCC";

    protected $objectName = "mail_message_emis";
    protected $mail_transaction_id;
    protected $email;
    protected $type_envoi;
    protected $ack;
    protected $ack_date;
    protected $dbFields = [
        'mail_transaction_id' => ['descr' => 'Identifiant utilisateur', 'type' => 'isInt', 'mandatory' => true],
        'email' => ['descr' => '---', 'type' => 'isString', 'mandatory' => true],
        'type_envoi' => ['descr' => '---', 'type' => 'isString', 'mandatory' => true],
        'ack' => ['descr' => '---', 'type' => 'isBool', 'mandatory' => true],
        'ack_date' => ['descr' => 'pfff', 'type' => 'isDate', 'mandatory' => false],
    ];


    /**
     * this new save is special
     * cause the id is in md5 and can not use default parent::save()
     * parent::save() always try to save the record with id in int and auto inc
     *
     * en plus: si il y a un mail adress de cc = mail adress de to par example
     * il va envois 2 mail sur un même adresse. et le destinataire faut confirmer 2 fois.
     *
     *
     * @param string $email = mail address emis
     * @param int $mail_transaction_id
     * @param string $type_envois with 3 type : mailto, mailcc, mailbcc
     * @return bool // return true if save success; or false if failed.
     */
    public function newSave($email, $mail_transaction_id, $type_envois)
    {
        $this->mail_transaction_id = $mail_transaction_id;
        $this->email = $email;
        $this->type_envoi = $type_envois;
        $now = date("Y-m-d H:i:s");
        $this->id = md5($email . $type_envois . $now);
        $this->ack = 0;
        $sql = "INSERT INTO mail_message_emis (id, mail_transaction_id, email, type_envoi, ack) VALUES (?,?,?,?,?)";
        $params = [$this->id, $mail_transaction_id, $email, $type_envois, strval($this->ack)];

        return $this->db->exec($sql, $params);
    }


    public function getMailTransactionId()
    {
        return $this->mail_transaction_id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getTypeEnvoi()
    {
        return $this->type_envoi;
    }

    public function getAck()
    {
        return $this->ack;
    }

    public function getAckDate()
    {
        return $this->ack_date;
    }

    public function acquitter()
    {
        $mailTransaction = new MailTransaction($this->getMailTransactionId());
        $mailTransaction->init();

        if ($this->get("ack") == 't') {
            return;
        }

        $this->set("ack", true);
        $this->set("ack_date", date("Y-m-d H:i:s"));
        $this->save(false);

        $date = date('Y-m-d H:i:s');
        $message = sprintf("Dossier %s retiré le %s par %s", $mailTransaction->getObjet(), $date, $this->getEmail());
        Log::newEntry(LOG_ISSUER_NAME, $message, 1, $date, 'USER', "mail", false, $mailTransaction->getUser_id());
    }
}
