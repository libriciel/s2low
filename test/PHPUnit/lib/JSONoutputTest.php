<?php

class JSONoutputTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var JSONoutput
	 */
	private $jsonOutput;

	public function setUp() : void {
		parent::setUp();
		$this->jsonOutput = new JSONoutput();
	}

	public function testDisplay(){
		$this->expectOutputRegex("#\[\]#");
		$this->jsonOutput->display(array());
	}

	public function testDisplayErrorAndExit(){
		$this->expectOutputRegex('#\{"status":"error","error-message":"foo"\}#');
		$this->setExpectedException("Exception","Exit !");
		$this->jsonOutput->displayErrorAndExit("foo");
	}

	public function testRestrictAndDisplay(){
		$data = array(array('foo'=>'bar','fii'=>'baz'));
		$this->expectOutputRegex('#\[\{"foo":"bar"\}\]#');
		$this->jsonOutput->retrictAndDisplay($data,array('foo'));
	}

}