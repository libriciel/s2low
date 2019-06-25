<?php

trait HeliosUtilitiesTestTrait {

	protected function createTransaction(){
		$sql = "INSERT INTO helios_transactions(user_id,authority_id,last_status_id,filename) VALUES (?,?,?,?) returning ID;";
		return $this->getSQLQuery()->queryOne($sql,1,1,4,"toto.txt");
	}

	/**
	 * @return SQLQuery
	 */
	abstract public function getSQLQuery();

}