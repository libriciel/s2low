<?php

trait MailsecUtilitiesTestTrait {

	private $fn_download_payload = "fn_download_test";

	protected function createMailTransaction() : int {
		return $this->getSQLQuery()->queryOne(
			"INSERT INTO mail_transaction(user_id,objet,message,date_envoi,fn_download,status) " .
			" VALUES (?,?,?,now(),?,?) RETURNING id",
			1,"test","message",$this->fn_download_payload,"aucune confirmation"
		);
	}

	/**
	 * @throws Exception
	 */
	protected function addFile($mail_transaction_id,$filename = "foo.pdf",$filetype="application/pdf",$filesize="42"){
		$this->getSQLQuery()->query(
			"INSERT INTO mail_included_file (mail_transaction_id, filename, filetype, filesize) VALUES (?,?,?,?)",
			$mail_transaction_id,$filename,$filetype,$filesize
		);
	}

	protected function getArchivePath($mail_files_upload_root = "/tmp"){
		return sprintf(
			"%s/%s/%s",
			$mail_files_upload_root,
			$this->fn_download_payload,
			MailsecDownloadController::DEFAULT_ARCHIVE_NAME
		);
	}

	/**
	 * @return SQLQuery
	 */
	abstract public function getSQLQuery();

}

