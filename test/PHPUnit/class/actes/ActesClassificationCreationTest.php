<?php

class ActesClassificationCreationTest extends S2lowTestCase {

	public function testsendToAllAuthorities(){
		$actesClassificationCreation = new ActesClassificationCreation();
		$this->expectOutputRegex("#Bourg-en-Bresse - id 1 :\[OK\]#");
		$actesClassificationCreation->sendToAllAuthorities();
	}

}
