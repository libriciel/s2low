<?php

namespace S2lowLegacy\Model;

use Exception;
use S2lowLegacy\Lib\SQL;
use S2lowLegacy\Lib\SQLQuery;

class MessageAdminSQL extends SQL
{
    private $cachePath;

    public function __construct(
        SQLQuery $sqlQuery,
        $cachePath,
    ) {
        parent::__construct($sqlQuery);
        $this->cachePath = $cachePath;
    }

    /**
     * @param $offset
     * @param $limit
     * @return MessageAdmin[]
     */
    public function getAll($offset, $limit)
    {
        $sql = "SELECT * FROM message_admin ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $result = array();
        foreach ($this->query($sql) as $message_info) {
            $result[] = $this->getMessageFromInfo($message_info);
        }
        return $result;
    }

    public function getMessage($message_id)
    {
        $message_info = $this->getInfo($message_id);
        return $this->getMessageFromInfo($message_info);
    }

    private function getMessageFromInfo($message_info)
    {
        $message = new MessageAdmin($this->cachePath);
        if ($message_info) {
            $message->message_id = $message_info['id'];
            $message->niveau = $message_info['niveau'];
            $message->message = $message_info['message'];
            $message->date_publication = $message_info['date_publication'];
            $message->date_retrait = $message_info['date_retrait'];
            $message->user_id = $message_info['user_id'];
            $message->is_publie = $message_info['is_publie'];
            $message->is_retire = $message_info['is_retire'];
            $message->titre = $message_info['titre'];
            $message->user_id_publieur = $message_info['user_id_publieur'];
            $message->user_id_retireur = $message_info['user_id_retireur'];
            if (isset($message_info['givenname'])) {
                $message->user_name = $message_info['givenname'] . " " . $message_info['name'];
            }
            if (isset($message_info['publieur_name'])) {
                $message->user_publieur_name = $message_info['publieur_givenname'] . " " . $message_info['publieur_name'];
            }
            if (isset($message_info['retireur_name'])) {
                $message->user_retireur_name = $message_info['retireur_givenname'] . " " . $message_info['retireur_name'];
            }
        }
        return $message;
    }

    public function getInfo($message_id)
    {
        $sql = "SELECT message_admin.*,users.givenname,users.name,publieur.givenname as publieur_givenname, publieur.name as publieur_name,retireur.givenname as retireur_givenname, retireur.name as retireur_name  FROM message_admin " .
                " LEFT JOIN users ON message_admin.user_id=users.id " .
                " LEFT JOIN users as publieur ON message_admin.user_id_publieur=publieur.id " .
                " LEFT JOIN users as retireur ON message_admin.user_id_retireur=retireur.id " .
                " WHERE message_admin.id=?";
        return $this->queryOne($sql, $message_id);
    }

    public function edit($message_id, $titre, $message, $user_id, $niveau)
    {
        if ($message_id) {
            $messageAdmin = $this->getMessage($message_id);
            if ($messageAdmin->getEtat() != MessageAdmin::ETAT_EN_COURS_DE_REDACTION) {
                throw new Exception("Impossible de modifier ce message qui n'est pas en cours de rédaction");
            }
            $sql = "UPDATE message_admin set titre=?,message=?,niveau=? WHERE id=?";
            $this->query($sql, $titre, $message, $niveau, $message_id);
        } else {
            $sql = "INSERT INTO message_admin(titre,message,user_id,niveau) VALUES(?,?,?,?) RETURNING id";
            $message_id = $this->queryOne($sql, $titre, $message, $user_id, $niveau);
        }
        return $message_id;
    }

    public function publier($message_id, $user_id)
    {
        $sql = "UPDATE message_admin SET date_retrait=now(),is_retire=true,user_id_retireur=? WHERE is_publie=true AND is_retire=false";
        $this->query($sql, $user_id);
        $sql = "UPDATE message_admin SET date_publication=now(),is_publie=true,user_id_publieur=? WHERE id=?";
        $this->query($sql, $user_id, $message_id);
    }

    public function retirer($message_id, $user_id)
    {
        $sql = "UPDATE message_admin SET date_retrait=now(),is_retire=true,user_id_retireur=? WHERE id=?";
        $this->query($sql, $user_id, $message_id);
    }

    public function getPublishedMessage()
    {
        $sql = "SELECT * FROM message_admin WHERE is_publie=true AND is_retire=false";
        return $this->getMessageFromInfo($this->queryOne($sql));
    }

    public function getMessages()
    {
        $sql = "SELECT * FROM message_admin WHERE is_publie=true AND is_retire=false";
        return $this->queryOne($sql);
    }
}
