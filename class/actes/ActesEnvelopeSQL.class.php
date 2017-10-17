<?php 


class ActesEnvelopeSQL {
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
		
	public function getInfo($id){
		$sql = "SELECT * FROM actes_envelopes WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
}