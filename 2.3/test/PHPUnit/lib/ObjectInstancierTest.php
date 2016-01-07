<?php

class ObjectInstancierTest extends PHPUnit_Framework_TestCase {
	
	public function testRecupValue(){
		$objectInstancier = new ObjectInstancier();
		$objectInstancier->foo = 'bar';
		$this->assertEquals('bar', $objectInstancier->foo);
	}
	
	public function testMakeObject(){
		$objectInstancier = new ObjectInstancier();
		require_once(__DIR__."/fixtures/MockClass.class.php");
		$mockClass = $objectInstancier->MockClass;
		$this->assertInstanceOf('MockClass', $mockClass);
	}
	
	
	public function testMakeObjectParamFail(){
		$objectInstancier = new ObjectInstancier();
		require_once(__DIR__."/fixtures/MockClassParam.class.php");
		$this->setExpectedException("Exception","Impossible d'instancier MockClassParam car le parametre param est manquant");
		$mockClass = $objectInstancier->MockClassParam;
	}
	
	public function testMakeObjectParam(){
		$objectInstancier = new ObjectInstancier();
		$objectInstancier->param = 42;
		require_once(__DIR__."/fixtures/MockClassParam.class.php");
		$mockClass = $objectInstancier->MockClassParam;
		$this->assertInstanceOf('MockClassParam', $mockClass);
	}
	
	public function testMakeObjectParamOptionnal(){
		$objectInstancier = new ObjectInstancier();
		require_once(__DIR__."/fixtures/MockClassParamOptional.class.php");
		$mockClass = $objectInstancier->MockClassParamOptional;
		$this->assertInstanceOf('MockClassParamOptional', $mockClass);
	}
	
}  