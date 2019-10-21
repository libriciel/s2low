<?php

use PHPUnit\Framework\TestCase;

class S2lowSimpleTestCase extends TestCase {

    protected function setUp() : void {
        parent::setUp();
        ObjectInstancierFactory::setObjectInstancier(new ObjectInstancier());
        $this->getObjectInstancier()->set("Monolog\Logger", new  Monolog\Logger('PHPUNIT'));
        $testHandler = new Monolog\Handler\TestHandler();
        $testHandler->setLevel(\Monolog\Logger::DEBUG);
        $this->getObjectInstancier()->set("Monolog\Handler\TestHandler", $testHandler);
        $this->getObjectInstancier()->get("Monolog\Logger")->pushHandler($testHandler);
    }

    public function getObjectInstancier() {
        return ObjectInstancierFactory::getObjetInstancier();
    }


	public function getLogRecords(){
		$testHandler = $this->getObjectInstancier()->get("Monolog\Handler\TestHandler");
		return $testHandler->getRecords();
	}
	/** @deprecated  */
	public function setExpectedException(string $e,string $message){
		$this->expectException($e);
		$this->expectExceptionMessage($message);
	}
}