<?php 


class ActesTransactionsSQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM actes_transactions WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	public function getStatusInfo($id,$status){
		$sql = "SELECT * FROM actes_transactions_workflow WHERE transaction_id=? AND status_id=?";
		return $this->sqlQuery->queryOne($sql,$id,$status);
	}
	
	public function getAllFile($id){
		$sql = "SELECT * FROM actes_included_files WHERE transaction_id=? ORDER BY id";
		return $this->sqlQuery->query($sql,$id);
	}
	
	public function setArchiveURL($transaction_id,$archive_url){
		$sql = "UPDATE actes_transactions SET archive_url=? " .
    			" WHERE id=?";
		$this->sqlQuery->query($sql,$archive_url,$transaction_id);
	}
	
	public function updateStatus($transaction_id,$status_id,$message,$flux_retour=''){
  	
	    $date = date("Y-m-d H:i:s");
	    $sql = "INSERT INTO actes_transactions_workflow (transaction_id, status_id, date, message,flux_retour) " .
	    		" VALUES( ? , ? , ? , ? ,?)";

  		$this->sqlQuery->query($sql,$transaction_id,$status_id,$date,$message,$flux_retour);
  
    	$sql = "UPDATE actes_transactions SET last_status_id=? " .
    			" WHERE id=?";
    	$this->sqlQuery->query($sql,$status_id,$transaction_id);
	}
	
	public function getArchiveFromStatus($status_id){
		$sql = "SELECT  *,actes_transactions.id as id FROM actes_transactions " .
				" JOIN authorities ON actes_transactions.authority_id=authorities.id " .
				" WHERE last_status_id=? AND authorities.sae_wsdl IS NOT NULL AND authorities.sae_wsdl != '' ";
		return $this->sqlQuery->query($sql,$status_id);	
	}
	
	public function getEnvelopeToDelete(){
		$sql = "SELECT  actes_envelopes.*,actes_transactions.id as transaction_id, actes_transactions.user_id " .
				" FROM actes_transactions " .
				" JOIN actes_envelopes ON actes_transactions.envelope_id=actes_envelopes.id " .
				" WHERE last_status_id=13 OR last_status_id = 5";
		return $this->sqlQuery->query($sql);	
	}
	
}