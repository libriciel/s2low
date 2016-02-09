<?php

class RecuperateurTest extends PHPUnit_Framework_TestCase {

	public function testGet(){
		$get = array('foo'=>'bar');
		$recuperateur = new Recuperateur($get);
		$this->assertEquals('bar',$recuperateur->get('foo'));
	}

	public function testGetEmpty(){
		$get = array('foo'=>'bar');
		$recuperateur = new Recuperateur($get);
		$this->assertFalse($recuperateur->get('baz'));
	}

	public function testGetInt(){
		$get = array('foo'=>'42bar');
		$recuperateur = new Recuperateur($get);
		$this->assertEquals(42,$recuperateur->getInt('foo'));
	}

}
