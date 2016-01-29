<?php 
class ActesTransactionsSQL {
	
	public function __construct(SQLQuery $sqlQuery){
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

	public function getLastStatusInfo($id){
		$sql = "SELECT * FROM actes_transactions_workflow WHERE transaction_id=? ORDER BY date DESC LIMIT 1";
		return $this->sqlQuery->queryOne($sql,$id);
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
	    		" VALUES( ? , ? , ? , ? ,?) RETURNING ID";

  		$id = $this->sqlQuery->queryOne($sql,$transaction_id,$status_id,$date,$message,$flux_retour);
  
    	$sql = "UPDATE actes_transactions SET last_status_id=? " .
    			" WHERE id=?";
    	$this->sqlQuery->query($sql,$status_id,$transaction_id);
		return $id;
	}
	
	public function getArchiveFStatus($status_id){
		$sql = "SELECT  actes_transactions.id as id FROM actes_transactions " .
				" WHERE last_status_id=? ";
		return $this->sqlQuery->query($sql,$status_id);
	}
	
	public function getArchiveFromStatus($status_id){
		$sql = "SELECT  *,actes_transactions.id as id FROM actes_transactions " .
				" JOIN authorities ON actes_transactions.authority_id=authorities.id " .
				" WHERE last_status_id=? AND authorities.pastell_url IS NOT NULL AND authorities.pastell_url != '' ";
		return $this->sqlQuery->query($sql,$status_id);	
	}
	
	public function getEnvelopeToDelete(){
		$sql = "SELECT  actes_envelopes.*,actes_transactions.id as transaction_id, actes_transactions.user_id " .
				" FROM actes_transactions " .
				" JOIN actes_envelopes ON actes_transactions.envelope_id=actes_envelopes.id " .
				" WHERE last_status_id=13 OR last_status_id = 5";
		return $this->sqlQuery->query($sql);	
	}
	
		
	public function getNextNumeroTransfert(){
		$sql = "SELECT count(*) + 1 FROM actes_transactions_workflow WHERE status_id=? AND date(date) = date(now());";
		return $this->sqlQuery->queryOne($sql,12);
	}
	
	public function getLatestDate($id){
		$all_id = array($id);
		$relatedTransaction = $this->getRelatedTransaction($id);
		foreach($relatedTransaction as $transaction){
			$all_id[] = $transaction['id'];
		}
		$sql = "SELECT max(date) " .
				" FROM actes_transactions_workflow " .
				" WHERE status_id IN (4,11,7) " .
				" AND transaction_id IN (".implode(",",$all_id).")";
		return $this->sqlQuery->queryOne($sql);
	}
	
	public function getRelatedTransaction($id){
		$result = array();
		$sql = "SELECT * ".
  				" FROM actes_transactions ".
  				" WHERE related_transaction_id=?";
		foreach($this->sqlQuery->query($sql,$id) as $line){
			$result[] = $line;
			$result = array_merge($result,$this->getRelatedTransaction($line['id']));
		}
		return $result;
	}
	
	public function setSAETransferIdentifier($id,$transfer_identifier){
		$sql = "UPDATE actes_transactions SET sae_transfer_identifier=? WHERE id=?";
		$this->sqlQuery->query($sql,$transfer_identifier,$id);
	}
	
	public function getDateTampon($id){
		$sql = "SELECT actes_envelopes.submission_date, actes_transactions_workflow.date, actes_transactions.unique_id " .
				"FROM actes_transactions, actes_envelopes, actes_transactions_workflow " .
				"WHERE actes_transactions.envelope_id = actes_envelopes.id " .
				"AND actes_transactions.id = " . $id .
				" AND actes_transactions_workflow.transaction_id = " . $id;
		return $this->sqlQuery->queryOne($sql);
	}
	
	public function getTransactionForAntiVirus(){
		$sql = "SELECT DISTINCT id FROM actes_transactions WHERE last_status_id=? AND antivirus_check=?";
		return $this->sqlQuery->queryOneCol($sql,1,0);
	}
	
	public function setAntivirusCheck($transaction_id){
		$sql = "UPDATE actes_transactions SET antivirus_check=? WHERE id=?";
		$this->sqlQuery->query($sql,1,$transaction_id);
	}
	
	
}