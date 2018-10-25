<?php

class ActesClassificationCreationTest extends S2lowTestCase {

	public function testsendToAllAuthorities(){
		$actesClassificationCreation = new ActesClassificationCreation();
		$this->expectOutputRegex("#Bourg-en-Bresse:\[OK\]#");
		$actesClassificationCreation->sendToAllAuthorities();
	}

}