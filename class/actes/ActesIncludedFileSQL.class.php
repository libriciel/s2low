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
	
	
}