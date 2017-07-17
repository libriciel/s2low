<?php

class FrontControllerTest extends PHPUnit_Framework_TestCase {

	public function testGo(){
		$objectInstancier = new ObjectInstancier();
        $get = array();
        $post = array();
        $request = array();
        $session = array();
        $server = array();
        $objectInstancier->set("Environnement", new Environnement($get,$post,$request,$session,$server));
		$frontController = new FrontController($objectInstancier);
		require_once(__DIR__."/fixtures/MockController.class.php");
		$this->expectOutputString("<h1>Mock Mock Template</h1>");
		$frontController->go("Mock","mock");
	}	
}