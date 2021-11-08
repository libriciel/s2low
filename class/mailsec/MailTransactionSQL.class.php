<?php

class MailTransactionSQL extends SQL {

	public function getTransactionIdToSendInCloud(){
		$sql = "SELECT id FROM mail_transaction WHERE fn_download IS NOT NULL AND is_in_cloud=FALSE AND not_available=FALSE ORDER BY id";
		return $this->queryOneCol($sql);
	}

	public function getFnDownload(int $mail_transaction_id){
		$sql = "SELECT fn_download FROM mail_transaction WHERE id=?";
		return $this->queryOne($sql,$mail_transaction_id);
	}

	public function setNotAvailable(int $mail_transaction_id){
		$sql = "UPDATE mail_transaction SET not_available=? WHERE id=?";
		$this->query($sql,true,$mail_transaction_id);
	}

	public function setInCloud(int $mail_transaction_id, $inCloud = true): void
	{
		$sql = "UPDATE mail_transaction SET is_in_cloud=? WHERE id=?";
		$this->query($sql, intval( $inCloud ), $mail_transaction_id);
	}

	public function fileExists(string $fn_download,string $filename){
		$sql = "SELECT count(*) FROM mail_transaction " .
			" JOIN mail_included_file ON mail_transaction.id=mail_included_file.mail_transaction_id " .
			" WHERE fn_download=? AND filename=?";
		return boolval($this->queryOne($sql,$fn_download,$filename));
	}

	public function fnDownloadExists($fn_download){
		$sql = "SELECT count(*) FROM mail_transaction WHERE fn_download=? ";
		return boolval($this->queryOne($sql,$fn_download));
	}

	public function getIdFromFnDownload($fn_download){
		$sql = "SELECT id FROM mail_transaction WHERE fn_download=? ";
		return $this->queryOne($sql,$fn_download);
	}

    public function getIdByFilename(string $fn_download)
    {
        $sql = "SELECT id FROM mail_transaction WHERE fn_download=?";
        return $this->queryOne($sql,$fn_download);
    }

    public function isAvailable(int $object_id): bool
    {
        $sql = "SELECT not_available FROM mail_transaction WHERE id=?";
        return ! $this->queryOne($sql,$object_id);
    }

    public function setAvailable(int $object_id, bool $available)
    {
        $sql = "UPDATE mail_transaction SET not_available=? WHERE id=?";
        $this->query($sql, intval(! $available),$object_id);
    }

    public function isInCloud(int $object_id)
    {
        $sql = "SELECT is_in_cloud FROM mail_transaction WHERE id=?";
        return ! $this->queryOne($sql,$object_id);
    }

}