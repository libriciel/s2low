<?php

class JSONoutputTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var JSONoutput
	 */
	private $jsonOutput;

	public function run(PHPUnit_Framework_TestResult $result = NULL) {
		$this->setPreserveGlobalState(false);
		$this->runTestInSeparateProcess = true;
		return parent::run($result);
	}

	public function setUp(){
		parent::setUp();
		$this->jsonOutput = new JSONoutput();
	}

	public function testDisplay(){
		$this->expectOutputString("[]");
		$this->jsonOutput->display(array());
	}

	public function testDisplayErrorAndExit(){
		$this->expectOutputString('{"status":"error","error-message":"foo"}');
		$this->setExpectedException("Exception","Exit !");
		$this->jsonOutput->displayErrorAndExit("foo");
	}

	public function testRestrictAndDisplay(){
		$data = array(array('foo'=>'bar','fii'=>'baz'));
		$this->expectOutputString('[{"foo":"bar"}]');
		$this->jsonOutput->retrictAndDisplay($data,array('foo'));
	}

}