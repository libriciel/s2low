<?php


class ActesCreator {

	private $actesTransactionsSQL;
	private $actesEnvelopeSQL;

	public function __construct(ActesTransactionsSQL $actesTransactionsSQL, ActesEnvelopeSQL $actesEnvelopeSQL) {
		$this->actesTransactionsSQL = $actesTransactionsSQL;
		$this->actesEnvelopeSQL = $actesEnvelopeSQL;
	}

	public function createTransaction($status,$archive_path,$tmp_dir){
		$archive_name = basename($archive_path);

		copy($archive_path,$tmp_dir."/$archive_name");

		$envelope_id = $this->actesEnvelopeSQL->create(1,basename($tmp_dir)."/$archive_name");

		$transaction_id = $this->actesTransactionsSQL->create($envelope_id,$status,1,1);

		$this->actesTransactionsSQL->updateStatus(
			$transaction_id,
			$status,
			"Création de la transaction via PHPUNIT"
		);
		return $transaction_id;
	}

}