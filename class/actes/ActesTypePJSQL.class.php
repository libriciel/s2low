<?php

class ActesTypePJSQL extends SQL {

    public function getAll(){
        $sql = "SELECT * FROM actes_type_pj ORDER BY nature_id,code";
        return $this->query($sql);
    }

    public function getAllByNature(){
        $result = array();
        foreach($this->getAll() as $type){
            $result[$type['nature_id']][$type['code']] =$type['libelle'];
        }
        return $result;
    }

    public function getLibelle($code){
        $sql = "SELECT libelle FROM actes_type_pj WHERE code=? LIMIT 1";
        return $this->queryOne($sql,$code);

    }

}