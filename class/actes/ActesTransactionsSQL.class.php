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
	
	public function updateStatus($transaction_id,$status_id,$message){
  	
	    $date = date("Y-m-d H:i:s");
	    $sql = "INSERT INTO actes_transactions_workflow (transaction_id, status_id, date, message) " .
	    		" VALUES( ? , ? , ? , ? )";

  		$this->sqlQuery->query($sql,$transaction_id,$status_id,$date,$message);
  
    	$sql = "UPDATE actes_transactions SET last_status_id=? " .
    			" WHERE id=?";
    	$this->sqlQuery->query($sql,$status_id,$transaction_id);
	}
	
}