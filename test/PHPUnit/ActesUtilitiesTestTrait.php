<?php
/**
 * Created by PhpStorm.
 * User: eric
 * Date: 21/08/2018
 * Time: 11:41
 */

trait ActesUtilitiesTestTrait
{

	private function getActesAPIController(){
		return $this->getObjectInstancier()->get(ActesAPIController::class);
	}


	/**
	 * @param $status
	 * @param string $archive_path
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	protected function createTransaction($status,$archive_path=""){
		$sql="INSERT INTO actes_envelopes(user_id,siren,department) VALUES(1,'000000000','034') returning ID";
		$envelope_id = $this->getSQLQuery()->queryOne($sql);

		$sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,decision_date,number,nature_code,type) VALUES (?,?,?,?,?,?,?,?) returning ID;";
		$transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,"2017-07-01","20170728C",3,1);

		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "test";
        $pastellProperties->login = "login";
        $pastellProperties->password = "password";
        $pastellProperties->id_e = 42;

		$authoritySQL->updateSAE(1,$pastellProperties);


		$actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

		$actesTransactionSQL->updateStatus($transaction_id,$status,"");

		if ($archive_path){
			$relative_path = basename($archive_path);
			$destination = $this->getObjectInstancier()->get("actes_files_upload_root")."/".basename($archive_path);
			copy($archive_path,$destination);
			$sql = "UPDATE actes_envelopes SET file_path=?,file_size=? WHERE id=?";
			$this->getSQLQuery()->query($sql,$relative_path,filesize($archive_path),$envelope_id);
		}

		$unique_id = $actesTransactionSQL->guessUniqueId($transaction_id);

		$sql = "UPDATE actes_transactions SET unique_id=? WHERE id=?";
		$this->getSQLQuery()->query($sql,$unique_id,$transaction_id);

		return $transaction_id;
	}


	/**
	 * @return SQLQuery
	 */
	abstract public function getSQLQuery();

	/**
	 * @return ObjectInstancier
	 */
	abstract public function getObjectInstancier();

}