<?php

class AuthorityTypesSQL extends SQL
{
    public function getChildList()
    {
        $sql = "SELECT id, description " .
                " FROM authority_types " .
                " WHERE parent_type_id IS NOT NULL " .
                " ORDER BY id ";

        return $this->query($sql);
    }

    public function getInfo($authority_type_id)
    {
        $sql = "SELECT * FROM authority_types WHERE id=?";
        return $this->queryOne($sql, $authority_type_id);
    }
}
