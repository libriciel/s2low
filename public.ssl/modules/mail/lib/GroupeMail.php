<?php

namespace S2lowLegacy\Mail;

use S2lowLegacy\Class\DataObject;

class GroupeMail extends DataObject
{
    protected $objectName = "mail_groupe";
    protected $id;
    protected $authority_id;
    protected $name;

    protected $dbFields =  array(
        "authority_id"          => array( "descr" => "Identifiant mail", "type" => "isInt", "mandatory" => true),
        "name"  => array("descr" => "---", "type" => "isString", "mandatory" => true),
    );

    public function __construct($id = false)
    {

        parent::__construct($id);
    }

    public function removeUser($id)
    {
        assert(!!$this->id);
        $sql = "DELETE FROM mail_user_groupe WHERE id_user=? AND id_groupe=?";
        $this->db->exec($sql, [$id,$this->id]);
    }

    public function isUserInGroup($id_user)
    {
        $sql = 'SELECT count(*) as nb FROM mail_user_groupe WHERE id_user=?;';
        $nb_groupe = $this->db->getOneValue($sql, [$id_user]);
        return $nb_groupe != 0;
    }

    public function getGroupeByAuthorityId($authority_id)
    {
        $sql = "SELECT * " .
                " FROM mail_groupe " .
                " WHERE authority_id=? " .
                " ORDER BY mail_groupe.name";
        $result = $this->db->select($sql, [$authority_id]);

        $tabResult = array();

        while ($ligne = $result->get_next_row()) {
            $tabResult[$ligne['id']] = $ligne;
            $tabResult[$ligne['id']]['nb_contact'] = 0;
        }
        $sql =  "SELECT count(*) as nb,id_groupe FROM mail_user_groupe " .
                " JOIN mail_groupe ON mail_user_groupe.id_groupe=mail_groupe.id " .
                " WHERE authority_id=? " .
                " GROUP BY mail_user_groupe.id_groupe";
        $result = $this->db->select($sql, [$authority_id]);
        while ($ligne = $result->get_next_row()) {
            $tabResult[$ligne['id_groupe']]['nb_contact'] = $ligne['nb'];
        }

        return $tabResult;
    }
}
