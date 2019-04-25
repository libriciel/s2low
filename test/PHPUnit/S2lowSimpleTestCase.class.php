<?php


class S2lowSimpleTestCase extends PHPUnit_Framework_TestCase {

    protected function setUp() {
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
}