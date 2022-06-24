<?php

class MailAnnuaireSQL
{
    public function __construct($sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    public function getInfo($id)
    {
        $sql = "SELECT * FROM mail_annuaire WHERE id=?";
        return $this->sqlQuery->queryOne($sql, $id);
    }
}
