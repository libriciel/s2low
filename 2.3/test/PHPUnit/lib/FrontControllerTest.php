<?php

class FrontControllerTest extends PHPUnit_Framework_TestCase {

	public function testGo(){
		$objectInstancier = new ObjectInstancier();
		$frontController = new FrontController($objectInstancier);
		require_once(__DIR__."/fixtures/MockController.class.php");
		$this->expectOutputString("<h1>Mock Mock Template</h1>");
		$frontController->go("Mock","mock");
	}	
}