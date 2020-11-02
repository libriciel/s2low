<?php

class ServiceUserSQL extends SQL
{
    public function getInfo(int $service_id)
    {
        $sql = "SELECT * FROM service_user WHERE id=?";
        return $this->queryOne($sql,$service_id);
    }

    function add($name,$authority_id){
        $sql = "SELECT * FROM service_user WHERE name=? AND authority_id=?";
        if ($this->query($sql,$name,$authority_id)){
            return false;
        }
        $sql = "INSERT INTO service_user(name,authority_id) VALUES (?,?) RETURNING id";
        return $this->queryOne($sql,$name,$authority_id);
    }
}
