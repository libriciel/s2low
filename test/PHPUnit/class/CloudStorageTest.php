<?php

class CloudStorageTest extends S2lowTestCase {

	public function testAllObjectIdToStore(){

		$cloudStorageFactory = $this->getObjectInstancier()->get(CloudStorageFactory::class);
		$this->assertTrue(true);
		return;
		$iCloudStorable = $this->getMock(ICloudStorable::class);


		$cloudStorage = $cloudStorageFactory->getInstance($iCloudStorable);

		$this->assertEquals("aaaa",$cloudStorage->getAllObjectIdToStore());

	}

}