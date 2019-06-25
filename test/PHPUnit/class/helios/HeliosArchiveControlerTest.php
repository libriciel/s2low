<?php

require_once SITEROOT."/class/helios/HeliosArchiveControler.class.php";

class HeliosArchiveControlerTest extends S2lowTestCase {

	use HeliosUtilitiesTestTrait;
	use PastellConfigurationTestTrait;

	/** @var  HeliosArchiveControler */
	private $heliosArchiveControler;

	/** @var  HeliosTransactionsSQL */
	private $heliosTransactionsSQL;


    protected function setUp(){
        parent::setUp();
        $this->heliosArchiveControler = $this->getObjectInstancier()->get(HeliosArchiveControler::class);
        $this->heliosTransactionsSQL = new HeliosTransactionsSQL($this->getSQLQuery());
    }

	public function testSend(){
		$this->setTransactionEnattente();
		$this->getObjectInstancier()->set(PastellWrapperFactory::class,$this->getPastellFactory());
		$this->getObjectInstancier()->unset_object(HeliosArchiveControler::class);
		$this->heliosArchiveControler = $this->getObjectInstancier()->get(HeliosArchiveControler::class);
		$this->expectOutputRegex("#Erreur renvoyé par le mock#");
		$this->heliosArchiveControler->sendAllArchive();
	}

	private function setTransactionEnattente(){
    	$this->configurePastell();
		$transaction_id = $this->createTransaction();
		$this->getObjectInstancier()
			->get(HeliosPrepareEnvoiSAE::class)
			->setArchiveEnAttenteEnvoiSEA(1,$transaction_id);
		return $transaction_id;
	}

	/**
	 * @param int $returnCreateActes
	 * @return PastellWrapperFactory
	 */
	private function getPastellFactory($returnCreateActes = 0){
		$pastell = $this->getMockBuilder('PastellWrapper')->disableOriginalConstructor()->getMock();
		$pastell->expects($this->any())->method('createActes')->willReturn($returnCreateActes);
		$pastell->expects($this->any())->method('getLastError')->willReturn("Erreur renvoyé par le mock");


		$pastellFactory = $this->getMockBuilder('PastellWrapperFactory')->disableOriginalConstructor()->getMock();
		$pastellFactory->expects($this->any())->method('getNewInstance')->willReturn($pastell);
		/** @var PastellWrapperFactory $pastellFactory */
		return $pastellFactory;
	}

	/**
	 * @throws Exception
	 */
	public function testSendTransactionEnErreur(){
		$this->setTransactionEnattente();
		$this->getObjectInstancier()->set(PastellWrapperFactory::class,$this->getPastellFactory());
		$this->getObjectInstancier()->unset_object(HeliosArchiveControler::class);
		$this->heliosArchiveControler = $this->getObjectInstancier()->get(HeliosArchiveControler::class);

		$this->expectOutputRegex("#Erreur renvoyé par le mock#");
		$this->heliosArchiveControler->sendAllArchive();
	}


}