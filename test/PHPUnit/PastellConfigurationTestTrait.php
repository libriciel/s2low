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

	protected function mockPastellFactory($id_d = "xyzt",$getLastErrorReturn=false){
		$pastell = $this->getMockBuilder('PastellWrapper')->disableOriginalConstructor()->getMock();
		$pastell->expects($this->any())->method('createActes')->willReturn($id_d);
		$pastell->expects($this->any())->method('createHelios')->willReturn($id_d);
		$pastell->expects($this->any())->method('getLastError')->willReturn($getLastErrorReturn);
		$pastell->expects($this->any())->method('sendSAE')->willReturn(true);
		$pastellFactory = $this->getMockBuilder('PastellWrapperFactory')->disableOriginalConstructor()->getMock();
		$pastellFactory->expects($this->any())->method('getNewInstance')->willReturn($pastell);
		$this->getObjectInstancier()->set(PastellWrapperFactory::class,$pastellFactory);
	}

	/**
	 * @return SQLQuery
	 */
	abstract public function getSQLQuery();

	/**
	 * @param $classname
	 * @return PHPUnit_Framework_MockObject_MockBuilder
	 */
	abstract public function getMockBuilder($classname);

	/**
	 * @return ObjectInstancier
	 */
	abstract public function getObjectInstancier();


}