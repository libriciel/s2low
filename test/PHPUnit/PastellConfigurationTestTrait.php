<?php

trait PastellConfigurationTestTrait {

	protected function configurePastell(){
		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$pastellProperties = new PastellProperties();
		$pastellProperties->url = "FakeURL";
		$pastellProperties->id_e = 12;
		$pastellProperties->actes_send_auto = true;
		$pastellProperties->helios_send_auto = true;
		$authoritySQL->updateSAE(1,$pastellProperties);
		$pastellPropertiesSQL  = new PastellPropertiesSQL($this->getSQLQuery());
		$pastellPropertiesSQL->editProperties(1,$pastellProperties);
	}

	/**
	 * @return SQLQuery
	 */
	abstract public function getSQLQuery();

}