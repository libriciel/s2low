<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\actes\ActesMenageEnveloppeWorker;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class ActesMenageEnveloppeWorkerTest extends S2lowTestCase
{
    /**
     * @return string
     * @throws Exception
     */
    private function createActesOnDisk(): string
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
        mkdir($tmp_folder . "/000000000/");
        $actes_path = $tmp_folder . "/000000000/test.tar.gz";
        file_put_contents("$actes_path", "foo");
        $this->getObjectInstancier()->set('actes_files_upload_root', $tmp_folder);
        return $actes_path;
    }

    /**
     * @param bool $fileExistsOnCloud
     */
    private function mockOpenStack(bool $fileExistsOnCloud = true): void
    {
        $openStackContainersManager =
            $this->getMockBuilder(OpenStackContainerStore::class)
                ->disableOriginalConstructor()
                ->getMock();

        $openStackContainersManager->expects($this->never())
            ->method($this->anything());

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper
            ->expects($this->never())
            ->method("retrieveFile");

        $openStackSwiftWrapper
            ->expects($this->never())
            ->method("deleteFile");

        $openStackSwiftWrapper
            ->method("fileExistsOnCloud")
            ->willReturn($fileExistsOnCloud);

        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);
    }

    /**
     * @throws Exception
     */
    public function testWhenIsInCloud()
    {
        $actes_path = $this->createActesOnDisk();
        $this->mockOpenStack();

        $this->assertFileExists($actes_path);
        $actesMenageEnveloppeWorker = $this->getObjectInstancier()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        $actesMenageEnveloppeWorker->work(false);
        $this->assertFileDoesNotExist($actes_path);
        $this->assertDirectoryDoesNotExist(dirname($actes_path));
    }

    /**
     * @throws Exception
     */
    public function testWhenIsNotInCloud()
    {
        $actes_path = $this->createActesOnDisk();
        $this->mockOpenStack(false);

        $this->assertFileExists($actes_path);
        $actesMenageEnveloppeWorker = $this->getObjectInstancier()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        $actesMenageEnveloppeWorker->work(false);
        $this->assertFileExists($actes_path);
        $this->assertDirectoryExists(dirname($actes_path));
    }

    /**
     * @throws Exception
     */
    public function testWithManyFiles()
    {
        $actes_path = $this->createActesOnDisk();
        file_put_contents(dirname($actes_path) . "/foo", "bar");
        $this->mockOpenStack(true);

        $this->assertFileExists($actes_path);
        $actesMenageEnveloppeWorker = $this->getObjectInstancier()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        $actesMenageEnveloppeWorker->work(false);
        $this->assertFileDoesNotExist($actes_path);
        $this->assertDirectoryExists(dirname($actes_path));
    }
}
