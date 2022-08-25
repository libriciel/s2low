<?php

namespace S2lowLegacy\Class\mailsec;

use S2lowLegacy\Lib\SQLQuery;

class MailAnnuaireSQL
{
    public function __construct(SQLQuery $sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    public function getInfo($id)
    {
        $sql = "SELECT * FROM mail_annuaire WHERE id=?";
        return $this->sqlQuery->queryOne($sql, $id);
    }
}
