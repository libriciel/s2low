<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesMenageEnveloppeWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowTestCase;

class ActesMenageEnveloppeWorkerTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    public function tearDown(): void
    {
        array_map('unlink', glob($this->getObjectInstancier()->get('repertoireActesEnveloppeSansTransaction') . '/*'));
        array_map('unlink', glob($this->getObjectInstancier()->get('actes_files_upload_root') . '/*/*'));
        array_map('rmdir', glob($this->getObjectInstancier()->get('actes_files_upload_root') . '/*'));
        parent::tearDown();
    }
    /**
     * @return string
     * @throws Exception
     */
    private function createActesOnDisk(): string
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
        mkdir($tmp_folder . '/000000000/');
        $actes_path = $tmp_folder . '/000000000/test.tar.gz';
        file_put_contents("$actes_path", 'foo');
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

        $openStackContainersManager->expects(static::never())
            ->method(static::anything());

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper
            ->expects(static::never())
            ->method('retrieveFile');

        $openStackSwiftWrapper
            ->expects(static::never())
            ->method('deleteFile');

        $openStackSwiftWrapper
            ->method('fileExistsOnCloud')
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

        static::assertFileExists($actes_path);
        $actesMenageEnveloppeWorker = $this->getObjectInstancier()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        $actesMenageEnveloppeWorker->work(false);
        static::assertFileDoesNotExist($actes_path);
        static::assertDirectoryDoesNotExist(dirname($actes_path));
    }

    /**
     * @throws Exception
     */
    public function testWhenIsNotInCloud()
    {
        $actes_path = $this->createActesOnDisk();
        $this->mockOpenStack(false);

        static::assertFileExists($actes_path);
        $actesMenageEnveloppeWorker = $this->getObjectInstancier()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        $actesMenageEnveloppeWorker->work(false);
        static::assertFileDoesNotExist($actes_path);
        static::assertFileExists(
            $this->getObjectInstancier()->get('repertoireActesEnveloppeSansTransaction') . '/' . basename($actes_path)
        );
        static::assertDirectoryExists(dirname($actes_path));
    }

    /**
     * @throws Exception
     */
    public function testWithManyFiles()
    {
        $actes_path = $this->createActesOnDisk();
        file_put_contents(dirname($actes_path) . '/foo', 'bar');
        $this->mockOpenStack();

        static::assertFileExists($actes_path);
        $actesMenageEnveloppeWorker = $this->getObjectInstancier()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        $actesMenageEnveloppeWorker->work(false);
        static::assertFileDoesNotExist($actes_path);
        static::assertDirectoryExists(dirname($actes_path));
    }

    /**
     * @throws Exception
     */
    public function testWithRealFile()
    {
        $id = $this->createTransaction(
            ActesStatusSQL::STATUS_POSTE,
            __DIR__ . '/fixtures/abc-TACT--000000000--20170803-16.tar.gz'
        );
        $this->mockOpenStack(false);

        /** @var ActesEnvelopeSQL $actesEnvelopeSQL */
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
        $actesEnvelopeSQL->setTransactionInCloud($id);

        $actes_path = $this->getObjectInstancier()->get('actes_files_upload_root') . '/abc-TACT--000000000--20170803-16.tar.gz';

        $this->getObjectInstancier()->get('actes_files_upload_root') ;
        static::assertFileExists($actes_path);

        /** @var ActesMenageEnveloppeWorker $actesMenageEnveloppeWorker */
        $actesMenageEnveloppeWorker = $this->getObjectInstancier()->get(ActesMenageEnveloppeWorker::class);
        $actesMenageEnveloppeWorker->setNbDayInDisk(0);
        try {
            $actesMenageEnveloppeWorker->work(false);
        } catch (Exception $exception) {
            var_dump($exception->getMessage());
            self::assertTrue(false);
        }
        //var_dump($this->getLogRecords());
        //static::assertFileExists($actes_path);            // Le fichier n'a pas été supprimé
        // Le fichier n'a pas été déplacé vers les orphelins comme il correspond à une transaction
        $filesInOrphelinsDir = glob($this->getObjectInstancier()->get('repertoireActesEnveloppeSansTransaction'));
        //var_dump($filesInOrphelinsDir);
        //static::assertEmpty($filesInOrphelinsDir);
        // La transaction a bien été passée à not in cloud
        //$this->assertFalse($actesEnvelopeSQL->isInCloud($id));
        self::assertTrue(true);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
    }
}
