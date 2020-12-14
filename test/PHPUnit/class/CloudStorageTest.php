<?php

use \Symfony\Component\Finder\Finder;

class CloudStorageTest extends S2lowTestCase {

	/**
	 * @param $file_path_on_disk
	 * @param string $file_path_on_cloud
	 * @param Finder|null $finder
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	private function getICloudStorable($file_path_on_disk,$file_path_on_cloud = "test42",Finder $finder = null){
		$iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
			->disableOriginalConstructor()
			->getMock();
		$iCloudStorable->method('getAllObjectIdToStore')->willReturn([42]);
		$iCloudStorable->method('getFilePathOnDisk')->willReturn($file_path_on_disk);
		$iCloudStorable->method('getFilePathOnCloud')->willReturn($file_path_on_cloud);
		if ($finder){
			$iCloudStorable->method("getFinder")->willReturn($finder);
		}

		return $iCloudStorable;
	}

	/**
	 * @param $iCloudStorable
	 * @return CloudStorage
	 */
	private function getCloudStorage($iCloudStorable){
		$cloudStorageFactory = $this->getObjectInstancier()->get(CloudStorageFactory::class);
		return $cloudStorageFactory->getInstance($iCloudStorable);
	}

	private function setOpenStackSwiftWrapper($fileExistsOnCloud = true,$sendFile = true){
		$openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
			->disableOriginalConstructor()
			->getMock();
		$openStackSwiftWrapper->method('sendFile')->willReturn($sendFile);
		$openStackSwiftWrapper->method('fileExistsOnCloud')->willReturn($fileExistsOnCloud);
		$this->getObjectInstancier()->set(OpenStackSwiftWrapper::class,$openStackSwiftWrapper);
		return $openStackSwiftWrapper;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private function createFile(){
		$tmpFolder = new TmpFolder();
		$tmp_folder = $tmpFolder->create();

		file_put_contents($tmp_folder."/foo.txt",'bar');
		return $tmp_folder."/foo.txt";
	}

	/**
	 * @throws Exception
	 */
	public function testAllObjectIdToStore(){
		$this->assertEquals(
			[42],
			$this->getCloudStorage($this->getICloudStorable(""))->getAllObjectIdToStore()
		);
	}

	/**
	 * @throws Exception
	 */
	public function testStoreObject(){
		$this->setOpenStackSwiftWrapper();
		$file_to_send = $this->createFile();
		$this->assertTrue(
			$this->getCloudStorage($this->getICloudStorable($file_to_send))
				->storeObject(42)
		);
		$this->assertLogMessage("Stored object [OK] : 42",3);
	}

	/**
	 * @throws Exception
	 */
	public function testStoreObjetWhenFileOnDiskNotAvailable(){
		$iCloudStorable = $this->getICloudStorable("");
		$this->assertFalse(
			$this->getCloudStorage($iCloudStorable)->storeObject(42)
		);
		$this->assertLogMessage(
			"Unable to store object #42 in cloud : file_path_on_disk not found !"
		);
	}

	/**
	 * @throws Exception
	 */
	public function testStoreObjetWhenFileOnDiskNotFound(){
		$iCloudStorable = $this->getICloudStorable("this_file_did_not_exists");
		$this->assertFalse(
			$this->getCloudStorage($iCloudStorable)->storeObject(42)
		);
		$this->assertLogMessage(
			"Unable to store object #42 in cloud : file this_file_did_not_exists did not exist !"
		);
	}

	/**
	 * @throws Exception
	 */
	public function testStoreObjetWhenFileOnCloudNotFound(){
		$file_to_send = $this->createFile();
		$iCloudStorable = $this->getICloudStorable($file_to_send,"");
		$this->assertFalse(
			$this->getCloudStorage($iCloudStorable)->storeObject(42)
		);
		$this->assertLogMessage(
			"Unable to store object #42 in cloud : file_path_on_cloud not found ?!?"
		);
	}

	/**
	 * @throws Exception
	 */
	public function testDeleteIfIsInCloud(){
		$this->setOpenStackSwiftWrapper();
		$file_to_send = $this->createFile();
		$iCloudStorable = $this->getICloudStorable($file_to_send,$file_to_send);
		$this->getCloudStorage($iCloudStorable)->deleteIfIsInCloud(42);
		$this->assertFileNotExists($file_to_send);
		$this->assertLogMessage("Deleting object #42 : $file_to_send");
	}

	/**
	 * @throws Exception
	 */
	public function testDeleteIfIsInCloudWhenNotOnCloud(){
		$this->setOpenStackSwiftWrapper(false);
		$file_to_send = $this->createFile();
		$iCloudStorable = $this->getICloudStorable($file_to_send,"foo");
		$this->getCloudStorage($iCloudStorable)->deleteIfIsInCloud(42);
		$this->assertFileExists($file_to_send);
		$this->assertLogMessage("Object #42 not existing on cloud : not deleted (foo not found)");
	}

	/**
	 * @throws Exception
	 */
	public function testDeleteIfIsInCloudWhenAnExceptionIsThrowing(){
		$openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
			->disableOriginalConstructor()
			->getMock();
		$openStackSwiftWrapper->method('sendFile')->willReturn(true);
		$openStackSwiftWrapper->method('fileExistsOnCloud')->willThrowException(
			new Exception("test unitaire")
		);
		$this->getObjectInstancier()->set(OpenStackSwiftWrapper::class,$openStackSwiftWrapper);
		$file_to_send = $this->createFile();
		$iCloudStorable = $this->getICloudStorable($file_to_send,"foo");

		$this->getCloudStorage($iCloudStorable)->deleteIfIsInCloud(42);
		$this->assertFileExists($file_to_send);
		$this->assertLogMessage(
			"Problème lors de la supression de l'objet #42 $file_to_send : test unitaire"
		);
	}

	private function assertNbJourDerniereModif(){
		$this->assertLogMessage(
			"Nombre de jour depuis la derniere modif : 0"
		);
	}

	/**
	 * @throws Exception
	 */
	public function testDeleteFileOnDisk(){
		$this->setOpenStackSwiftWrapper();
		$file_to_send = $this->createFile();

		$finder = new Finder();
		$finder->in(dirname($file_to_send));

		$iCloudStorable = $this->getICloudStorable($file_to_send,$file_to_send,$finder);

		$this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk();
		$this->assertFileExists($file_to_send);
		$this->assertNbJourDerniereModif();
		$this->assertLogMessage(
			"File foo.txt too young to die : not deleted",1
		);
	}

	/**
	 * @throws Exception
	 */
	public function testDeleteFileOnDiskWhenTooOld(){
		$this->setOpenStackSwiftWrapper();
		$file_to_send = $this->createFile();

		$finder = new Finder();
		$finder->in(dirname($file_to_send));

		$iCloudStorable = $this->getICloudStorable($file_to_send,$file_to_send,$finder);

		$this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
		$this->assertNbJourDerniereModif();
		$this->assertLogMessage(
			"Deleting file : $file_to_send",2
		);
	}


	/**
	 * @throws Exception
	 */
	public function testDeleteFileOnDiskWhenNotExistingOnCloud(){
		$this->setOpenStackSwiftWrapper(false);
		$file_to_send = $this->createFile();

		$finder = new Finder();
		$finder->in(dirname($file_to_send));

		$iCloudStorable = $this->getICloudStorable($file_to_send,"",$finder);

		$this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
		$this->assertFileExists($file_to_send);
		$this->assertNbJourDerniereModif();
		$this->assertLogMessage(
			"File $file_to_send not existing on cloud : not deleted",2
		);
	}

	public function testErrorWhileCreatingFile(){
        $this->setOpenStackSwiftWrapper(false,false);
        $file_to_send = $this->createFile();
        $this->assertFalse(
            $this->getCloudStorage($this->getICloudStorable($file_to_send))
                ->storeObject(42)
        );
    }

    public function testWhenObjectMarkedAsNotAvailable()
    {
        $this->setOpenStackSwiftWrapper(false,false);
        $file_to_send = $this->createFile();
        $finder = new Finder();
        $finder->in(dirname($file_to_send));
        $iCloudStorable = $this->getICloudStorable($file_to_send,"",$finder);
        $iCloudStorable->method("isAvailable")->willReturn(false);
        $iCloudStorable->method("getObjectIdByFilePath")->willReturn(42);
        $iCloudStorable->expects($this->once())->method("setAvailable")->with($this->equalTo(true));
        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0,true);
        $this->assertLogMessage("42 set to available",3);
    }

    public function testWhenObjectMarkedAsAvailable()
    {
        $this->setOpenStackSwiftWrapper(false,false);
        $file_to_send = $this->createFile();
        $finder = new Finder();
        $finder->in(dirname($file_to_send));
        $iCloudStorable = $this->getICloudStorable($file_to_send,"",$finder);
        $iCloudStorable->method("getObjectIdByFilePath")->willReturn(42);
        $iCloudStorable->method("isAvailable")->willReturn(true);
        $iCloudStorable->expects($this->never())->method("setAvailable");
        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0,true);
        $this->assertLogMessage("Object not yet in cloud",3);
    }


}