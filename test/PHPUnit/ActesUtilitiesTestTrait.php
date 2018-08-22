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


	protected function createTransaction($status){
		$sql="INSERT INTO actes_envelopes(user_id,siren,department) VALUES(1,'000000000','034') returning ID";
		$envelope_id = $this->getSQLQuery()->queryOne($sql);

		$sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,decision_date,number,nature_code,type) VALUES (?,?,?,?,?,?,?,?) returning ID;";
		$transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,"2017-07-01","20170728C",3,1);
		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$authoritySQL->updateSAE(1,array('pastell_url'=>'test','pastell_login'=>'test','pastell_password'=>'test','pastell_id_e'=>'12'));

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