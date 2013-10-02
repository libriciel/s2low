<?php 


class HeliosTransactionsSQL {
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getInfo($id){
		$sql = "SELECT * FROM helios_transactions WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	public function getLatestStatusId($id){
		$sql = "SELECT status_id " .
				" FROM helios_transactions_workflow " .
				" WHERE transaction_id=? " .
				" ORDER BY date DESC LIMIT 1";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	//Attention, implémentation partiel du last_status_id uniquement pour l'envoi au SAE 
	//Ce champ n'est pas maintenus nottamment dans la partie Java du TdT
	public function updateStatus($transaction_id,$status_id,$message){
	    $date = date("Y-m-d H:i:s");
	    $sql = "INSERT INTO helios_transactions_workflow (transaction_id, status_id, date, message) " .
	    		" VALUES( ? , ? , ? , ? )";
  		$this->sqlQuery->query($sql,$transaction_id,$status_id,$date,$message);
  		$sql = "UPDATE helios_transactions SET last_status_id=? " .
    			" WHERE id=?";
    	$this->sqlQuery->query($sql,$status_id,$transaction_id);
	}
	
	public function setSAETransferIdentifier($id,$transfer_identifier){
		$sql = "UPDATE helios_transactions SET sae_transfer_identifier=? WHERE id=?";
		$this->sqlQuery->query($sql,$transfer_identifier,$id);
	}
	
	public function getArchiveFromStatus($status_id){
		$sql = "SELECT  *,helios_transactions.id as id FROM helios_transactions " .
				" JOIN users ON helios_transactions.user_id = users.id " .
				" JOIN authorities ON users.authority_id=authorities.id " .
				" WHERE last_status_id=? AND authorities.sae_wsdl IS NOT NULL AND authorities.sae_wsdl != '' ";
		return $this->sqlQuery->query($sql,$status_id);	
	}
	
	public function setArchiveURL($transaction_id,$archive_url){
		$sql = "UPDATE helios_transactions SET archive_url=? " .
    			" WHERE id=?";
		$this->sqlQuery->query($sql,$archive_url,$transaction_id);
	}
}