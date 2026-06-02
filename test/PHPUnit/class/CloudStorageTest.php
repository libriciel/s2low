<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use Monolog\Level;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\ICloudStorable;
use S2lowLegacy\Class\mailsec\MailIncludedFilesCloudStorable;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\LogsRequestData;
use S2lowTestCase;
use Symfony\Component\Finder\Finder;
use UnexpectedValueException;

class CloudStorageTest extends S2lowTestCase
{
    private function getMailIncludedFilesCloudStorable(
        string $file_path_on_disk,
        ?string $getDirectoryForFilesWithoutTransaction = null,
        ?string $getPathRelativeToUploadDir = null,
    ): MailIncludedFilesCloudStorable | MockObject {
        $this->setOpenStackSwiftWrapper(false, false);
        $iCloudStorable = $this->getMockBuilder(MailIncludedFilesCloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();

        $finder = new Finder();
        $finder->in(dirname($file_path_on_disk));
        $iCloudStorable->method('getFinder')->willReturn($finder);

        $iCloudStorable->method('getDirectoryForFilesWithoutTransaction')
            ->willReturn($getDirectoryForFilesWithoutTransaction);

        $iCloudStorable->method('getDesiredPathInDirectoryForFilesWithoutTransaction')
                ->willReturn($getPathRelativeToUploadDir);

        $iCloudStorable->method('getObjectIdByFilePath')->willReturn(0);

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturn(basename($file_path_on_disk));

        return $iCloudStorable;
    }

    private function getICloudStorable(
        string $file_path_on_disk,
        string $file_path_on_cloud = 'test42',
        ?Finder $finder = null,
    ): ICloudStorable |MockObject {
        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $iCloudStorable->method('getAllObjectIdToStore')->willReturn([42]);
        $iCloudStorable->method('getFilePathOnDisk')->willReturn($file_path_on_disk);
        $iCloudStorable->method('getFilePathOnCloud')->willReturn($file_path_on_cloud);
        if ($finder) {
            $iCloudStorable->method('getFinder')->willReturn($finder);
        }

        return $iCloudStorable;
    }

    private function getCloudStorage(ICloudStorable $iCloudStorable): CloudStorage
    {
        return new CloudStorage(
            $iCloudStorable,
            self::getContainer()->get(OpenStackSwiftWrapper::class),
            $this->logger,
            true
        );
    }

    private function setOpenStackSwiftWrapper(
        bool $fileExistsOnCloud = true,
        bool $sendFile = true
    ): void {
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openStackSwiftWrapper->method('sendFile')->willReturn($sendFile);
        $openStackSwiftWrapper->method('fileExistsOnCloud')->willReturn($fileExistsOnCloud);
        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);
    }

    /**
     * @throws Exception
     */
    private function createFile(): string
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

        file_put_contents($tmp_folder . '/foo.txt', 'bar');
        return $tmp_folder . '/foo.txt';
    }

    /**
     * @throws Exception
     */
    public function testAllObjectIdToStore()
    {
        static::assertSame(
            [42],
            $this->getCloudStorage($this->getICloudStorable(''))->getAllObjectIdToStore()
        );
    }

    /**
     * @throws Exception
     */
    public function testStoreObject()
    {
        $this->setOpenStackSwiftWrapper();
        $file_to_send = $this->createFile();
        static::assertTrue(
            $this->getCloudStorage($this->getICloudStorable($file_to_send))
                ->storeObject(42)
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Stored object [OK] : 42",
                Level::Info
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testStoreObjetWhenFileOnDiskNotAvailable()
    {
        $iCloudStorable = $this->getICloudStorable('');
        static::assertFalse(
            $this->getCloudStorage($iCloudStorable)->storeObject(42)
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Unable to store object #42 in cloud : file_path_on_disk not found !",
                Level::Error
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testStoreObjetWhenFileOnDiskNotFound()
    {
        $iCloudStorable = $this->getICloudStorable('this_file_did_not_exists');
        static::assertFalse(
            $this->getCloudStorage($iCloudStorable)->storeObject(42)
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Unable to store object #42 in cloud : file this_file_did_not_exists did not exist !",
                Level::Error
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testStoreObjetWhenFileOnCloudNotFound()
    {
        $file_to_send = $this->createFile();
        $iCloudStorable = $this->getICloudStorable($file_to_send, '');
        static::assertFalse(
            $this->getCloudStorage($iCloudStorable)->storeObject(42)
        );

        self::assertTrue(
            $this->testHandler->hasRecord(
                "Unable to store object #42 in cloud : file_path_on_cloud not found ?!?",
                Level::Error
            )
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
        static::assertFileDoesNotExist($file_to_send);
        self::assertTrue(
            $this->testHandler->hasRecord(
                "Deleting object #42 : $file_to_send",
                Level::Info
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteIfIsInCloudWhenNotOnCloud()
    {
        $this->setOpenStackSwiftWrapper(false);
        $file_to_send = $this->createFile();
        $iCloudStorable = $this->getICloudStorable($file_to_send, 'foo');
        $this->getCloudStorage($iCloudStorable)->deleteIfIsInCloud(42);
        static::assertFileExists($file_to_send);
        self::assertTrue(
            $this->testHandler->hasRecord(
                "Object #42 not existing on cloud : not deleted (foo not found)",
                Level::Info
            )
        );
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
            new Exception('test unitaire')
        );
        $this->getObjectInstancier()->set(OpenStackSwiftWrapper::class, $openStackSwiftWrapper);
        $file_to_send = $this->createFile();
        $iCloudStorable = $this->getICloudStorable($file_to_send, 'foo');

        $this->getCloudStorage($iCloudStorable)->deleteIfIsInCloud(42);
        static::assertFileExists($file_to_send);
        self::assertTrue(
            $this->testHandler->hasRecord(
                "Problème lors de la supression de l'objet #42 $file_to_send : test unitaire",
                Level::Alert
            )
        );
    }

    private function assertNbJourDerniereModif(): void
    {
        self::assertTrue(
            $this->testHandler->hasRecord(
                "Nombre de jour depuis la derniere modif : 0",
                Level::Debug,
            ),
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
        static::assertFileExists($file_to_send);
        $this->assertNbJourDerniereModif();

        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                "File foo.txt too young to die : not deleted",
                Level::Debug
            )
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

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturn(basename($file_to_send));

        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        $this->assertNbJourDerniereModif();

        self::assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Deleting file : $file_to_send",
                Level::Info
            )
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

        $iCloudStorable = $this->getICloudStorable($file_to_send, '', $finder);

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturn(basename($file_to_send));

        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        static::assertFileExists($file_to_send);
        $this->assertNbJourDerniereModif();

        self::assertTrue(
            $this->testHandler->hasRecord(
                "File $file_to_send not existing on cloud : not deleted",
                Level::Info
            )
        );
    }

    /**
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     * @throws \S2lowLegacy\Class\CloudStorageException
     * @throws \Exception
     */
    public function testErrorWhileCreatingFile()
    {
        $this->setOpenStackSwiftWrapper(false, false);
        $file_to_send = $this->createFile();
        static::assertFalse(
            $this->getCloudStorage($this->getICloudStorable($file_to_send))
                ->storeObject(42)
        );
    }

    /**
     * @throws \Exception
     */
    public function testWhenObjectMarkedAsNotAvailableAndNotInCloud()
    {
        $this->setOpenStackSwiftWrapper(false, false);
        $file_to_send = $this->createFile();
        $finder = new Finder();
        $finder->in(dirname($file_to_send));
        $iCloudStorable = $this->getICloudStorable($file_to_send, '', $finder);
        $iCloudStorable->method('isAvailable')->willReturn(false);
        $iCloudStorable->method('getObjectIdByFilePath')->willReturn(42);
        $iCloudStorable->expects(static::once())->method('setAvailable')->with(static::equalTo(true));

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturn(basename($file_to_send));

        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        self::assertTrue(
            $this->testHandler->hasRecord(
                "42 set to available",
                Level::Info
            )
        );
    }

    /**
     * @dataProvider pathsProvider
     * @throws Exception
     */
    public function testMoveToOrphelinsFile(string $path_relative_to_upload_dir): void
    {
        $file_to_send = $this->createFile();

        $tmpDir = new TmpFolder();
        $files_without_transaction_dir = $tmpDir->create();
        $iCloudStorable = $this->getMailIncludedFilesCloudStorable(
            $file_to_send,
            $files_without_transaction_dir,
            $path_relative_to_upload_dir
        );

        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        self::assertTrue(
            $this->testHandler->hasRecordThatMatches(
                "#Unable to find object id for the file#",
                Level::Notice
            )
        );
        self::assertTrue(
            $this->testHandler->hasRecord(
                "rename done to $path_relative_to_upload_dir in $files_without_transaction_dir",
                Level::Info
            )
        );

        static::assertFileDoesNotExist($file_to_send); //Le fichier original est supprimé
        static::assertFileExists(
            $files_without_transaction_dir . '/' . $path_relative_to_upload_dir
        ); // Un fichier est créé dans le répertoire des fichiers sans transaction

        $tmpDir->delete($files_without_transaction_dir);
    }

    public function pathsProvider(): iterable
    {
        return [
            ['bar.txt'],        // cas ou le fichier est directement dans le répertoire d'upload
            ['foo/bar.txt']     // cas ou le fichier est dans un répertoire situé dans le répertoire d'upload
        ];
    }

    /**
     * @throws \Exception
     */
    public function testMoveToOrphelinsFileTooManyDirectories(): void
    {
        $file_to_send = $this->createFile();

        $tmpDir = new TmpFolder();
        $files_without_transaction_dir = $tmpDir->create();
        $iCloudStorable = $this->getMailIncludedFilesCloudStorable(
            $file_to_send,
            $files_without_transaction_dir,
            'foo/bar/baz.txt'
        );

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('cas non implémenté (trop de sous-répertoires dans foo/bar/baz.txt)');
        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        $tmpDir->delete($files_without_transaction_dir);
    }

    /**
     * @throws \Exception
     */
    public function testMoveToOrphelinsDifferentFileAlreadyExistsWithSameName(): void
    {
        $file_to_send = $this->createFile();
        $tmpDir = new TmpFolder();
        $files_without_transaction_dir = $tmpDir->create();
        $iCloudStorable = $this->getMailIncludedFilesCloudStorable(
            $file_to_send,
            $files_without_transaction_dir,
            'bar.txt'
        );

        file_put_contents($files_without_transaction_dir . '/bar.txt', 'la place est prise');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le fichier $files_without_transaction_dir/bar.txt existe déjà");
        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        $tmpDir->delete($files_without_transaction_dir);
    }

    /**
     * @throws \Exception
     */
    public function testMoveToOrphelinsSameFileAlreadyExists(): void
    {
        $file_to_send = $this->createFile();
        $tmpDir = new TmpFolder();
        $files_without_transaction_dir = $tmpDir->create();
        $iCloudStorable = $this->getMailIncludedFilesCloudStorable(
            $file_to_send,
            $files_without_transaction_dir,
            'bar.txt'
        );

        file_put_contents(
            $files_without_transaction_dir . '/bar.txt',
            file_get_contents($file_to_send)
        );

        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        static::assertFileDoesNotExist($file_to_send); //Le fichier original est supprimé

        $tmpDir->delete($files_without_transaction_dir);
    }

    /** @dataProvider availabilityAndCloudProvider
     * @throws \Exception
     */
    public function testAvailabilityAndInCloud(
        bool $isAvailable,
        bool $isTransactionInCloud,
        int $nbOfSetAvailableCalls,
        int $nbOfsetInCloudCalls,
        array $logs
    ) {
        $this->setOpenStackSwiftWrapper(false, false);
        $file_to_send = $this->createFile();
        $finder = new Finder();
        $finder->in(dirname($file_to_send));
        $iCloudStorable = $this->getICloudStorable($file_to_send, '', $finder);
        $iCloudStorable->method('getObjectIdByFilePath')->willReturn(42);
        $iCloudStorable->method('isAvailable')->willReturn($isAvailable);
        $iCloudStorable->method('isTransactionInCloud')->willReturn($isTransactionInCloud);
        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturn(basename($file_to_send));
        $iCloudStorable->expects(static::exactly($nbOfSetAvailableCalls))->method('setAvailable');
        $iCloudStorable->expects(static::exactly($nbOfsetInCloudCalls))->method('setInCloud');
        $this->getCloudStorage($iCloudStorable)->deleteFilesOnDisk(0);
        foreach ($logs as $log) {
            self::assertTrue(
                $this->testHandler->hasRecordThatMatches(
                    $log,
                    Level::Info,
                )
            );
        }
    }

    public function availabilityAndCloudProvider(): array
    {
        // Si on arrive à la partie testée, le fichier a été trouvé sur le disque mais pas dans le cloud.
        // S'il est marqué comme non available en BDD, il faut corriger : il est au moins sur le disque.
        // S'il est marqué comme sur le cloud en BDD, il faut corriger : il n'y est pas.
        return [
            'withBothAvailableAndTransactionInCloud' =>
                [
                    true,
                    true,
                    0,
                    1,
                    [
                        '#passé à is_in_cloud = false#',
                    ]
                ],
            'withNotAvailableAndTransactionInCloud' =>
                [
                    false,
                    true,
                    1,
                    1,
                    [
                        '#set to available#',
                        '#passé à is_in_cloud = false#',
                    ]
                ],
            'withOnlyAvailable' =>
                [true, false, 0, 0, []],
            'withNotAvailableAndNotInCloud' =>
                [
                    false,
                    false,
                    1,
                    0,
                    [
                        '#set to available#',
                    ]
                ]
        ];
    }

    public function testFileNotIncloud()
    {
        $filePathOnDisk = '/test/test/test.tar.gz';

        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturnArgument(0);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::exactly(2))
            ->method('fileExistsOnCloud')
            ->with(null, $filePathOnDisk)
            ->willReturn(false);

        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cloudStorage = new CloudStorage($iCloudStorable, $openStackSwiftWrapper, $logger, true);

        $return = $cloudStorage->getFilePathOnCloudWithFileOnDiskPath($filePathOnDisk);

        static::assertSame(
            $filePathOnDisk,
            $return
        );
    }

    public function testFileInCloudWithSamePath()
    {
        $filePathOnDisk = '/test/test/test.tar.gz';

        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturnArgument(0);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::once())->method('fileExistsOnCloud')
            ->with(null, $filePathOnDisk)
            ->willReturn(true);

        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cloudStorage = new CloudStorage($iCloudStorable, $openStackSwiftWrapper, $logger, true);

        $return = $cloudStorage->getFilePathOnCloudWithFileOnDiskPath($filePathOnDisk);

        static::assertSame(
            $filePathOnDisk,
            $return
        );
    }

    public function testFileInCloudWithDoubleSlash()
    {
        $filePathOnDisk = '/test/import/test.tar.gz';

        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iCloudStorable->method('getFilePathOnCloudWithFileOnDiskPath')
            ->willReturnArgument(0);

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::exactly(2))
            ->method('fileExistsOnCloud')
            ->withConsecutive(
                [static::equalTo(null), static::equalTo($filePathOnDisk)],
                [static::equalTo(null), static::equalTo('/test/import//test.tar.gz')]
            )->willReturnOnConsecutiveCalls(false, true);

        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cloudStorage = new CloudStorage($iCloudStorable, $openStackSwiftWrapper, $logger, true);

        $return = $cloudStorage->getFilePathOnCloudWithFileOnDiskPath($filePathOnDisk);

        static::assertSame(
            '/test/import//test.tar.gz',
            $return
        );
    }

    /**
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     * @throws \S2lowLegacy\Class\CloudStorageException
     */
    public function testGetPath()
    {
        $iCloudStorable = $this->getMockBuilder(ICloudStorable::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iCloudStorable->method('getFilePathOnDisk')
            ->willReturn('/idontexist/idontexist/testidontexist.tar.gz');

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackSwiftWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cloudStorage = new CloudStorage($iCloudStorable, $openStackSwiftWrapper, $logger, false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Unable to retrieve /idontexist/idontexist/testidontexist.tar.gz and no cloud storage enabled'
        );
        $cloudStorage->getPath(1);
    }
}
