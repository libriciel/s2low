<?php

use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2low\Services\RemoveOldFilesOnDisk;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesMenageEnveloppeWorker;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use Symfony\Component\Filesystem\Filesystem;

class ActesMenageEnveloppeWorkerTest extends S2lowTestCase
{
    const RELATIVE_FILE_PATH = '000000000/test.tar.gz';
    use ActesUtilitiesTestTrait;

    /**
     * @return string
     * @throws Exception
     */
    private function createActesOnDisk(): string
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
        mkdir($tmp_folder . "/000000000/");
        $actes_path = $tmp_folder . "/" . self::RELATIVE_FILE_PATH;
        file_put_contents("$actes_path", "foo");
        return $tmp_folder;
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
        $tmpFolder = $this->createActesOnDisk();
        $actes_path = $tmpFolder . '/' . self::RELATIVE_FILE_PATH;

        $removeActeEnveloppe = $this->createRemoveActeEnveloppe(true, $tmpFolder);
        self::getContainer()->set('app.removeFiles.acte_enveloppe', $removeActeEnveloppe);

        $this->assertFileExists($actes_path);
        $this->createEnveloppe(self::RELATIVE_FILE_PATH);
        $actesMenageEnveloppeWorker = self::getContainer()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        $actesMenageEnveloppeWorker->work(null);
        $this->assertFileDoesNotExist($actes_path);
        $this->assertDirectoryDoesNotExist(dirname($actes_path));
    }

    /**
     * @throws Exception
     */
    public function testWhenIsNotInCloud()
    {
        $tmpFolder = $this->createActesOnDisk();
        $actes_path = $tmpFolder . '/' . self::RELATIVE_FILE_PATH;

        $this->createEnveloppe(self::RELATIVE_FILE_PATH);
        $removeActeEnveloppe = $this->createRemoveActeEnveloppe(false, $tmpFolder);
        self::getContainer()->set('app.removeFiles.acte_enveloppe', $removeActeEnveloppe);

        $this->assertFileExists($actes_path);
        $actesMenageEnveloppeWorker = self::getContainer()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        $actesMenageEnveloppeWorker->work(null);
        static::assertFileExists(
            $actes_path
        );
        static::assertDirectoryExists(dirname($actes_path));
    }

    /**
     * @throws Exception
     */
    public function testWithManyFiles()
    {
        $tmpFolder = $this->createActesOnDisk();
        $actes_path = $tmpFolder . '/' . self::RELATIVE_FILE_PATH;

        $this->createEnveloppe(self::RELATIVE_FILE_PATH);
        $removeActeEnveloppe = $this->createRemoveActeEnveloppe(true, $tmpFolder);
        self::getContainer()->set('app.removeFiles.acte_enveloppe', $removeActeEnveloppe);
        file_put_contents(dirname($actes_path) . "/foo", "bar");

        $this->assertFileExists($actes_path);
        $actesMenageEnveloppeWorker = self::getContainer()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        $actesMenageEnveloppeWorker->work(null);

        $this->assertFileDoesNotExist($actes_path);
        $this->assertDirectoryExists(dirname($actes_path));
    }

    private function createRemoveActeEnveloppe(bool $fileExistsOnCloud, string $prefix): RemoveOldFilesOnDisk
    {
        $cloudFileStorage = self::getMockBuilder(CloudFileStorageInterface::class)->disableOriginalConstructor()->getMock();
        $cloudFileStorage->method('fileExistOnCloud')->willReturn($fileExistsOnCloud);

        $localFileResolver = new LocalFileResolver(
            self::getContainer()->get(ActesEnvelopeSQL::class),
            $prefix
        );

        return new RemoveOldFilesOnDisk(
            $this->logger,
            self::getContainer()->get(Filesystem::class),
            $cloudFileStorage,
            self::getContainer()->get(ActesEnvelopeSQL::class),
            $localFileResolver,
            self::getContainer()->get('app.finder.acte_enveloppe'),
            self::getContainer()->getParameter('app.actes_enveloppe_sans_transaction'),
            true
        );
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        self::getContainer()->get(ActesTransactionsSQL::class);
    }
}
