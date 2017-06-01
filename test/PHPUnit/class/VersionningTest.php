<?php

class VersionningTest extends PHPUnit_Framework_TestCase {

	public function testVersion(){

		$versionning = VersionningFactory::getInstance();
		$this->assertEquals(
			array(
				'version'=>'WIP',
				'revision' => '000',
				'date'=>'01/01/1970',
				'version-complete' => 'Version WIP - Révision  000 - 01/01/1970'
			),
			$versionning->getAllInfo());

	}


}