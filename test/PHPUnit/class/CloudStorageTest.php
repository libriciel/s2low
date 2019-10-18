<?php

class CloudStorageTest extends S2lowTestCase {

	public function testAllObjectIdToStore(){

		$cloudStorageFactory = $this->getObjectInstancier()->get(CloudStorageFactory::class);
		return;
		$iCloudStorable = $this->getMock(ICloudStorable::class);


		$cloudStorage = $cloudStorageFactory->getInstance($iCloudStorable);

		$this->assertEquals("aaaa",$cloudStorage->getAllObjectIdToStore());

	}

}