<?php

namespace S2lowLegacy\Mail;

//Les classes MailPeer et MailTransaction ne sont pas utilisable pour gérer l'API... (EP)

use S2lowLegacy\Class\Database;

class MailList
{
    private $db;
    private $id_user;

    public function __construct(Database $db, $id_user)
    {
        $this->db = $db;
        $this->id_user = $id_user;
    }

    public function getNb()
    {
        return $this->db->getOneValue("SELECT count(*) FROM mail_transaction WHERE user_id=?", [$this->id_user]);
    }

    public function getListMail($offset = 0, $limit = 20)
    {

        if (! $offset) {
            $offset = 0;
        }
        if (! $limit) {
            $limit = 20;
        }

        $limit = (int) $limit;
        $offset = (int) $offset;
        $sql = "SELECT * FROM mail_transaction WHERE  user_id=? ORDER BY date_envoi DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$this->id_user, $limit, $offset]);
    }

    public function getDetail($id_mail_transaction)
    {
        $result = $this->db->getOneLine("SELECT * FROM mail_transaction WHERE id=? AND user_id=?", [$id_mail_transaction , $this->id_user]);
        if (! $result) {
            return false;
        }
        $sql = "SELECT * FROM mail_message_emis WHERE mail_transaction_id=?";
        $result['mail_emis'] =  $this->db->fetchAll($sql, [$id_mail_transaction]);

        $sql = "SELECT * FROM mail_included_file WHERE mail_transaction_id=?";

        $result['file'] = $this->db->fetchAll($sql, [$id_mail_transaction]);
        return $result;
    }

    public function delete($id_mail_transaction)
    {


        $sql = "DELETE FROM mail_errors " .
             " WHERE id IN " .
                "(SELECT mail_message_emis.mail_transaction_id FROM mail_message_emis " .
                " WHERE mail_transaction_id=?)";
        $this->db->exec($sql, [$id_mail_transaction]);

        $sql = "DELETE FROM mail_message_emis WHERE mail_transaction_id=?";
        $this->db->exec($sql, [$id_mail_transaction]);


        $sql = "DELETE FROM mail_included_file WHERE mail_transaction_id=?";
        $this->db->exec($sql, [$id_mail_transaction]);

        $sql = "DELETE FROM mail_transaction WHERE id=?";
        $this->db->exec($sql, [$id_mail_transaction]);
    }
}
