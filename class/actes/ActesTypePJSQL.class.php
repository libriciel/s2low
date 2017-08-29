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

}