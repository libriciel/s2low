<?php 


class ActesEnvelopeSQL extends SQL {

	public function getInfo($id){
		$sql = "SELECT * FROM actes_envelopes WHERE id=?";
		return $this->queryOne($sql,$id);
	}

	public function findByAnomalieEnveloppeName($anomalie_enveloppe_name){
        $file_path = "%" .substr($anomalie_enveloppe_name,4,-3)."%";
		$sql = "SELECT id FROM actes_envelopes  WHERE file_path LIKE ? ";
		return $this->queryOne($sql,$file_path);
    }

    public function create($user_id,$file_path){
        $sql="INSERT INTO actes_envelopes(user_id,file_path) VALUES(?,?) returning ID";
        return $this->getSQLQuery()->queryOne($sql,$user_id,$file_path);
    }

}