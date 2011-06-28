<?php 

class ActesStatistiques {
	
	private $sqlQuery;
	private $filter;
	private $value;
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
		$this->filter = array("actes_transactions_workflow.status_id=?","actes_transactions_workflow.date>?");
		$this->value = array(1,'1970-01-01');
	}
	
	public function setUser($user_id){
		$this->filter[] = "actes_transactions.user_id=?";
		$this->value[] = $user_id;
	}
	public function setAuthority($authority_id){
		$this->filter[] = "actes_transactions.authority_id=?";
		$this->value[] = $authority_id;
	}
	
	public function setGroup($group_id){
		$this->filter[] = "authority_group_id=?";
		$this->value[] = $group_id;
	}
	
	public function getInfo(){
		
		$allDate = array(date("Y-m-01"),date("Y-01-01"),'1970-01-01');
		foreach($allDate as $date){
			$result[$date]['nb_envelope'] =  $this->getWithParam(1,$date);
			$result[$date]['nb_envelope_poste'] =  $this->getWithParam(3,$date);
			$result[$date]['volume'] =  $this->getVolume(1,$date);
			$result[$date]['volume_poste'] =  $this->getVolume(3,$date);
		}
		
		return $result;
	}
	
	private function getWithParam($status,$min_date){
		$sql = "SELECT count(actes_envelopes.id) FROM actes_envelopes"
	  				. " JOIN users ON actes_envelopes.user_id=users.id"
	 		 	. " JOIN actes_transactions ON actes_transactions.envelope_id=actes_envelopes.id"
	 		 	. " JOIN actes_transactions_workflow " .
	 		 	" ON actes_transactions_workflow.transaction_id=actes_transactions.id";
			
	 	$this->value[0] = $status;
	 	$this->value[1] = $min_date;
	 	
		$sql .=  " WHERE  " . implode(" AND " , $this->filter);
		return $this->sqlQuery->queryOne($sql,$this->value);
	}	
	
	public function getVolume($status,$min_date){
		$sql = "SELECT SUM(actes_envelopes.file_size) AS sum FROM actes_envelopes actes_envelopes"
		  . " LEFT JOIN users ON actes_envelopes.user_id=users.id"
		  . " LEFT JOIN actes_transactions ON actes_transactions.envelope_id=actes_envelopes.id"
		  . " JOIN actes_transactions_workflow ON actes_transactions_workflow.transaction_id=actes_transactions.id";
	
		$this->value[0] = $status;
	 	$this->value[1] = $min_date;
	 	
		$sql .=  " WHERE  " . implode(" AND " , $this->filter);
		return $this->sqlQuery->queryOne($sql,$this->value);
	}

}