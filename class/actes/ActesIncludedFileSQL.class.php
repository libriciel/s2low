<?php
class ActesIncludedFileSQL extends SQL {

	public function getSendFile($transaction_id){
		$sql = "SELECT * FROM actes_included_files WHERE transaction_id=? AND sha1 != '' ORDER BY id";
		return  $this->query($sql,$transaction_id);
	}
	
	public function setSignature($transaction_id,$included_file_id,$signature){
		$sql = "UPDATE actes_included_files SET signature=? WHERE id=? AND transaction_id=?";
		$this->query($sql,$signature,$included_file_id,$transaction_id);
	}
	
	/**
	 * @return string le nom du fichier XML decrivant le fichier contenant l'acte
	 */
	public function getXMLFilename($transaction_id){
		$sql = "SELECT filename FROM actes_included_files WHERE transaction_id=? ORDER BY id ASC LIMIT 1";
		return $this->queryOne($sql,$transaction_id);
	}
	
	public function getTransactionId($actes_included_file_id){
		$sql = "SELECT transaction_id FROM actes_included_files WHERE id=?";
		return $this->queryOne($sql,$actes_included_file_id);
	}
	
	public function getActesFileInfo($transaction_id){
		$sql = "SELECT * FROM actes_included_files WHERE transaction_id=? ORDER BY id ASC OFFSET 1 LIMIT 1";
		return $this->queryOne($sql,$transaction_id);
	}

    public function addIncludedFile($envelope_id,$transaction_id,$content_type,$filesize,$filename) {
        $sql = "INSERT into actes_included_files(envelope_id," .
                "transaction_id,filename," .
                " filetype,filesize,signature,posted_filename) VALUES (?,?,?,?,?,?,?) RETURNING id";
        return $this->queryOne($sql,$envelope_id,$transaction_id,$filename,$content_type,$filesize,null,$filename);
    }

    public function getAll($transaction_id){
        $sql = "SELECT * FROM actes_included_files WHERE transaction_id=? ORDER BY id ASC";
        return $this->query($sql,$transaction_id);
    }

    public function getAllFilenameInEnvelope($envelope_id){
        $sql = "SELECT filename FROM actes_included_files WHERE envelope_id=? ORDER BY id ASC";
        return $this->queryOneCol($sql,$envelope_id);
    }

}