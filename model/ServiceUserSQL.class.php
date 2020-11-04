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

    public function enleverUser(int $id_service,int $id_user): void
    {
        $sql = "DELETE FROM service_user_content WHERE id_service=? AND id_user=?";
        $this->query($sql,$id_service,$id_user);
    }

    public function supprimerService(int $service_id): void{
        $sql = "DELETE FROM service_user WHERE id=?";
        $this->query($sql,$service_id);
    }
}
