<?php

use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\ICloudStorable;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use Monolog\Logger;
use Symfony\Component\Finder\Finder;

class CloudStorageTest extends S2lowTestCase
{
    /**
     * @param $file_path_on_disk
     * @param string $file_path_on_cloud
     * @param Finder|null $finder
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getICloudStorable($file_path_on_disk, $file_path_on_cloud = "test42", Finder $finder = null)
    {
        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $iCloudStorable->method('getAllObjectIdToStore')->willReturn([42]);
        $iCloudStorable->method('getFilePathOnDisk')->willReturn($file_path_on_disk);
        $iCloudStorable->method('getFilePathOnCloud')->willReturn($file_path_on_cloud);
        if ($finder) {
            $iCloudStorable->method("getFinder")->willReturn($finder);
        }

        return $iCloudStorable;
    }

    /**
     * @param $iCloudStorable
     * @return CloudStorage
     */
    private function getCloudStorage($iCloudStorable)
    {
        $cloudStorageFactory = $this->getObjectInstancier()->get(CloudStorageFactory::class);
        return $cloudStorageFactory->getInstance($iCloudStorable);
    }

    private function setOpenStackSwiftWrapper($fileExistsOnCloud = true, $sendFile = true)
    {
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openStackSwiftWrapper->method('sendFile')->willReturn($sendFile);
        $openStackSwiftWrapper->method('fileExistsOnCloud')->willReturn($fileExistsOnCloud);
        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);
        return $openStackSwiftWrapper;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function createFile()
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

        file_put_contents($tmp_folder . "/foo.txt", 'bar');
        return $tmp_folder . "/foo.txt";
    }

    /**
     * @throws Exception
     */
    public function testAllObjectIdToStore()
    {
        $this->assertEquals(
            [42],
            $this->getCloudStorage($this->getICloudStorable(""))->getAllObjectIdToStore()
        );
    }

    /**
     * @throws Exception
     */
    public function testStoreObject()
    {
        $this->setOpenStackSwiftWrapper();
        $file_to_send = $this->createFile();
        $this->assertTrue(
            $this->getCloudStorage($this->getICloudStorable($file_to_send))
                ->storeObject(42)
        );
        $this->assertLogMessage("Stored object [OK] : 42", 3);
    }

    /**
     * @throws Exception
     */
    public function testStoreObjetWhenFileOnDiskNotAvailable()
    {
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
    public function testStoreObjetWhenFileOnDiskNotFound()
    {
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
    public function testStoreObjetWhenFileOnCloudNotFound()
    {
        $file_to_send = $this->createFile();
        $iCloudStorable = $this->getICloudStorable($file_to_send, "");
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
    public function testDeleteIfIsInCloud()
    {
        $this->setOpenStackSwiftWrapper();
        $file_to_send = $this->createFile();
        $iCloudStorable = $this->getICloudStorable($file_to_send, $file_to_send);
        $this->getCloudStorage($iCloudStorable)->deleteIfIsInCloud(42);
        $this->assertFileDoesNotExist($file_to_send);
        $this->assertLogMessage("Deleting object #42 : $file_to_send");
    }

    /**
     * @throws Exception
     */
    public function testDeleteIfIsInCloudWhenNotOnCloud()
    {
        $this->setOpenStackSwiftWrapper(false);
        $file_to_send = $this->createFile();
        $iCloudStorable = $this->getICloudStorable($file_to_send, "foo");
        $this->getCloudStorage($iCloudStorable)->deleteIfIsInCloud(42);
        $this->assertFileExists($file_to_send);
        $this->assertLogMessage("Object #42 not existing on cloud : not deleted (foo not found)");
    }

    /**
     * @throws Exception
     */
    public function testDeleteIfIsInCloudWhenAnExceptionIsThrowing()
    {
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openStackSwiftWrapper->method('sendFile')->willReturn(true);
        $openStackSwiftWrapper->method('fileExistsOnCloud')->willThrowException(
            new Exception("test unitaire")
        );
        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);
        $file_to_send = $this->createFile();
        $iCloudStorable = $this->getICloudStorable($file_to_send, "foo");

        $this->getCloudStorage($iCloudStorable)->deleteIfIsInCloud(42);
        $this->assertFileExists($file_to_send);
        $this->assertLogMessage(
            "Problème lors de la supression de l'objet #42 $file_to_send : test unitaire"
        );
    }

    private function assertNbJourDerniereModif()
    {
        $this->assertLogMessage(
            "Nombre de jour depuis la derniere modif : 0"
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteFileOnDisk()
    {
        $this->setOpenStackSwiftWrapper();
        $file_to_send = $this->createFile();

        $finder = new Finder();
        $finder->in(dirname($file_to_send));

        $iCloudStorable = $this->getICloudStorable($file_to_send, $file_to_send, $finder);

        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk();
        $this->assertFileExists($file_to_send);
        $this->assertNbJourDerniereModif();
        $this->assertLogMessage(
            "File foo.txt too young to die : not deleted",
            1
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteFileOnDiskWhenTooOld()
    {
        $this->setOpenStackSwiftWrapper();
        $file_to_send = $this->createFile();

        $finder = new Finder();
        $finder->in(dirname($file_to_send));

        $iCloudStorable = $this->getICloudStorable($file_to_send, $file_to_send, $finder);

        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        $this->assertNbJourDerniereModif();
        $this->assertLogMessage(
            "Deleting file : $file_to_send",
            2
        );
    }


    /**
     * @throws Exception
     */
    public function testDeleteFileOnDiskWhenNotExistingOnCloud()
    {
        $this->setOpenStackSwiftWrapper(false);
        $file_to_send = $this->createFile();

        $finder = new Finder();
        $finder->in(dirname($file_to_send));

        $iCloudStorable = $this->getICloudStorable($file_to_send, "", $finder);
        $iCloudStorable->method("getObjectIdByFilePath")->willReturn(42);

        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        $this->assertFileExists($file_to_send);
        $this->assertNbJourDerniereModif();
        $this->assertLogMessage(
            "File $file_to_send not existing on cloud : not deleted",
            2
        );
    }

    public function testErrorWhileCreatingFile()
    {
        $this->setOpenStackSwiftWrapper(false, false);
        $file_to_send = $this->createFile();
        $this->assertFalse(
            $this->getCloudStorage($this->getICloudStorable($file_to_send))
                ->storeObject(42)
        );
    }

    public function testWhenObjectMarkedAsNotAvailableAndNotInCloud()
    {
        $this->setOpenStackSwiftWrapper(false, false);
        $file_to_send = $this->createFile();
        $finder = new Finder();
        $finder->in(dirname($file_to_send));
        $iCloudStorable = $this->getICloudStorable($file_to_send, "", $finder);
        $iCloudStorable->method("isAvailable")->willReturn(false);
        $iCloudStorable->method("getObjectIdByFilePath")->willReturn(42);
        $iCloudStorable->expects($this->once())->method("setAvailable")->with($this->equalTo(true));
        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0, true);
        $this->assertLogMessage("42 set to available", 3);
    }

    /** @dataProvider availabilityAndCloudProvider */
    public function testAvailabilityAndInCloud(bool $isAvailable, bool $isTransactionInCloud, int $nbOfSetAvailableCalls, int $nbOfsetInCloudCalls, array $logs)
    {
        $this->setOpenStackSwiftWrapper(false, false);
        $file_to_send = $this->createFile();
        $finder = new Finder();
        $finder->in(dirname($file_to_send));
        $iCloudStorable = $this->getICloudStorable($file_to_send, "", $finder);
        $iCloudStorable->method("getObjectIdByFilePath")->willReturn(42);
        $iCloudStorable->method("isAvailable")->willReturn($isAvailable);
        $iCloudStorable->method("isTransactionInCloud")->willReturn($isTransactionInCloud);
        $iCloudStorable->expects($this->exactly($nbOfSetAvailableCalls))->method("setAvailable");
        $iCloudStorable->expects($this->exactly($nbOfsetInCloudCalls))->method("setInCloud");
        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0, true);
        foreach ($logs as $key => $line) {
            $this->assertMatchesRegularExpressionLogMessage($line, $key);
        }
    }

    public function availabilityAndCloudProvider(): array
    {
        // Si on arrive à la partie testée, le fichier a été trouvé sur le disque mais pas dans le cloud.
        // S'il est marqué comme non available en BDD, il faut corriger : il est au moins sur le disque.
        // S'il est marqué comme sur le cloud en BDD, il faut corriger : il n'y est pas.
        return [
            "withBothAvailableAndTransactionInCloud" => [true, true, 0, 1, ["3" => "#passé à is_in_cloud = false#"]],
            "withNotAvailableAndTransactionInCloud" => [false, true, 1, 1,  ["3" => "#set to available#","4" => "#passé à is_in_cloud = false#"]],
            "withOnlyAvailable" => [true, false, 0, 0,  []],
            "withNotAvailableAndNotInCloud" => [false,false, 1, 0,["3" => "#set to available#"]]
        ];
    }

    public function testNoTransaction()
    {
        $this->setOpenStackSwiftWrapper(false, false);
        $tmpFolder = new TmpFolder();
        $inputFolder = $tmpFolder->create();
        $outputFolder = $tmpFolder->create();
        mkdir($inputFolder . "/dir1/dir2/", 0700, true);
        $file_to_send =  $inputFolder . "/dir1/dir2/foo.txt";
        file_put_contents($file_to_send, 'bar');
        $finder = new Finder();
        $finder->in(dirname($file_to_send));
        $iCloudStorable = $this->getICloudStorable($file_to_send, "", $finder);
        $iCloudStorable->method("getObjectIdByFilePath")->willReturn(null);
        $iCloudStorable->method("getRootPath")->willReturn($inputFolder);
        $iCloudStorable->method("getNoRelatedOjectInDBDirectory")->willReturn($outputFolder);
        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0, true);
        $this->assertFalse(file_exists($inputFolder . "/dir1/dir2/foo.txt"));
        $this->assertTrue(file_exists($outputFolder . "/dir1/dir2/foo.txt"));
        $this->assertEquals(
            'bar',
            file_get_contents($outputFolder . "/dir1/dir2/foo.txt")
        );
    }

    public function testMoveFileFromDirToOtherDir()
    {
        $this->setOpenStackSwiftWrapper(false, false);
        $finder = new Finder();
        $iCloudStorable = $this->getICloudStorable("", "", $finder);
        $this->assertEquals(
            "/dir/2/to/file",
            $this->getCloudStorage($iCloudStorable)
                ->moveFileFromDirToOtherDir("/path/1/to/file", "/path/1/", "/dir/2/")
        );
    }

    public function testFileNotIncloud()
    {
        $filePathOnDisk = "/test/test/test.tar.gz";

        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturnArgument(0);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->exactly(2))->method('fileExistsOnCloud')->with(null, $filePathOnDisk)->willReturn(false);

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cloudStorage = new CloudStorage($iCloudStorable, $openStackSwiftWrapper, $logger, true);

        $return = $cloudStorage->getFilePathOnCloudWithFileOnDiskPath($filePathOnDisk);

        $this->assertEquals(
            $filePathOnDisk,
            $return
        );
    }

    public function testFileInCloudWithSamePath()
    {
        $filePathOnDisk = "/test/test/test.tar.gz";

        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturnArgument(0);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->once())->method('fileExistsOnCloud')->with(null, $filePathOnDisk)->willReturn(true);

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cloudStorage = new CloudStorage($iCloudStorable, $openStackSwiftWrapper, $logger, true);

        $return = $cloudStorage->getFilePathOnCloudWithFileOnDiskPath($filePathOnDisk);

        $this->assertEquals(
            $filePathOnDisk,
            $return
        );
    }

    public function testFileInCloudWithDoubleSlash()
    {
        $filePathOnDisk = "/test/import/test.tar.gz";

        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturnArgument(0);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->exactly(2))
            ->method('fileExistsOnCloud')
            ->withConsecutive(
                [$this->equalTo(null), $this->equalTo($filePathOnDisk)],
                [$this->equalTo(null), $this->equalTo("/test/import//test.tar.gz")]
            )->willReturnOnConsecutiveCalls(false, true);

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cloudStorage = new CloudStorage($iCloudStorable, $openStackSwiftWrapper, $logger, true);

        $return = $cloudStorage->getFilePathOnCloudWithFileOnDiskPath($filePathOnDisk);

        $this->assertEquals(
            "/test/import//test.tar.gz",
            $return
        );
    }

    public function testGetPath()
    {
        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iCloudStorable->method('getFilePathOnDisk')
            ->willReturn("/idontexist/idontexist/testidontexist.tar.gz");

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cloudStorage = new CloudStorage($iCloudStorable, $openStackSwiftWrapper, $logger, false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Unable to retrieve /idontexist/idontexist/testidontexist.tar.gz and no cloud storage enabled"
        );
        $cloudStorage->getPath(1);
    }
}
