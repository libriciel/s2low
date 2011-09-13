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
	
	public function archiver($transaction_id,$message,$archive_url){
		$this->updateStatus($transaction_id,12,$message);
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
	
}