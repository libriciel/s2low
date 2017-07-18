<?php 


class ActesEnvelopeSQL extends SQL {

	public function getInfo($id){
		$sql = "SELECT * FROM actes_envelopes WHERE id=?";
		return $this->queryOne($sql,$id);
	}


	
}