<?php

class AuthoritySiretSQL extends SQL
{
    public function siretList($authority_id)
    {
        $sql = "SELECT * FROM authority_siret WHERE authority_id=? and is_blocked=FALSE ORDER BY siret";
        return $this->query($sql, $authority_id);
    }


    public function siretListBlocked($authority_id)
    {
        $sql = "SELECT * FROM authority_siret WHERE authority_id=? and is_blocked=TRUE ORDER BY siret";
        return $this->query($sql, $authority_id);
    }

    public function add($authority_id, $siret)
    {
        $sql = "SELECT id FROM authority_siret WHERE authority_id=? AND siret=?";
        $id = $this->queryOne($sql, $authority_id, $siret);
        if ($id) {
            return $id;
        }
        $sql = "INSERT INTO authority_siret(authority_id,siret,date) VALUES (?,?,now()) RETURNING id";
        return $this->queryOne($sql, $authority_id, $siret);
    }

    public function del($id)
    {
        $sql = "DELETE FROM authority_siret WHERE id=?";
        $this->query($sql, $id);
    }

    public function authorityList($siret)
    {
        $sql = "SELECT * FROM authority_siret WHERE siret=? AND is_blocked=FALSE";
        return $this->query($sql, $siret);
    }

    public function getInfo($authority_siret_id)
    {
        $sql = "SELECT * FROM authority_siret WHERE id=?";
        return $this->queryOne($sql, $authority_siret_id);
    }

    public function blocked($id)
    {
        $sql = "UPDATE authority_siret SET is_blocked=TRUE WHERE id=?";
        $this->query($sql, $id);
    }

    public function unblocked($id)
    {
        $sql = "UPDATE authority_siret SET is_blocked=FALSE WHERE id=?";
        $this->query($sql, $id);
    }
}
