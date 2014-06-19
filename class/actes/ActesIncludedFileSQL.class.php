<?php
class ActesIncludedFileSQL {
	
	private $sqlQuery;
	
	public function __construct($sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}
	
	public function getSendFile($transaction_id){
		$sql = "SELECT * FROM actes_included_files WHERE transaction_id=? AND sha1 != '' ";
		return  $this->sqlQuery->query($sql,$transaction_id);
	}
	
	public function setSignature($id,$signature_id,$signature){
		$sql = "UPDATE actes_included_files SET signature=? WHERE id=? AND transaction_id=?";
		$this->sqlQuery->query($sql,$signature,$signature_id,$id);
	}
	
	public function getXMLFilename($transaction_id){
		$sql = "SELECT filename FROM actes_included_files WHERE transaction_id=? ORDER BY id ASC LIMIT 1";
		return $this->sqlQuery->queryOne($sql,$transaction_id);
		
	}
	
	public function getTransactionId($actes_included_file_id){
		$sql = "SELECT transaction_id FROM actes_included_files WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$actes_included_file_id);
	}
	
	
	
}