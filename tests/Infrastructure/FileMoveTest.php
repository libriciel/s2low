<?php

namespace Infrastructure;

use Psr\Log\LoggerInterface;
use S2low\Infrastructure\Directory;
use S2low\Services\FilesAndDirectoriesUtils\FileMover;
use S2lowLegacy\Class\TmpFolder;
use S2lowTestCase;
use SplFileObject;
use Symfony\Component\Filesystem\Filesystem;

class FileMoveTest extends S2lowTestCase
{
    /**
     * @throws \Exception
     */
    public function testFileAlreadyInDirectory(): void
    {
        $tmpFolder = new TmpFolder();
        $directoryPath = $tmpFolder->create();

        $filePath = $directoryPath . '/file.txt';

        file_put_contents($filePath, 'touch');

        $file = new SplFileObject($filePath);

        $directory = new Directory(
            $directoryPath
        );

        $fileMover = new FileMover(
            self::getContainer()->get(Filesystem::class),
            self::getContainer()->get(LoggerInterface::class),
        );

        $fileMover->moveFileWithRename($file, $directory);

        self::assertEqualsCanonicalizing(
            ['.','..','file.txt'],
            scandir($directory->getPath())
        );

        $tmpFolder->delete($directoryPath);
    }
}
