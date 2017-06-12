<?php

class OpenStackSwiftCounterSQL extends SQL {

    public function getLastInsertId($container){
        $sql = "SELECT last_insert_id FROM openstack_swift_counter WHERE container=?";
        return $this->queryOne($sql,$container)?:0;
    }

    public function setLastInsertId($container,$last_insert_id){
        if($this->getLastInsertId($container)){
            $sql = "UPDATE openstack_swift_counter SET last_insert_id=? WHERE container=?";
            $this->query($sql,$last_insert_id,$container);
        } else {
            $sql = "INSERT INTO openstack_swift_counter (container, last_insert_id) VALUES (?,?)";
            $this->query($sql,$container,$last_insert_id);
        }
    }

}