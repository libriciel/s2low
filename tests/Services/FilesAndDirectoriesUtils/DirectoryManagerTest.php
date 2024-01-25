<?php

declare(strict_types=1);

namespace S2low\Tests\Services\FilesAndDirectoriesUtils;

use Exception;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use S2low\Services\FilesAndDirectoriesUtils\DirectoryManager;
use S2low\Services\FilesAndDirectoriesUtils\DirectoryManagerFactory;
use S2low\Services\FilesAndDirectoriesUtils\FileNamesHandler;
use SplFileInfo;

/**
 * Tests for the DirectoryManager
 */
class DirectoryManagerTest extends TestCase
{
    private string $outputStreamUrl;
    private string $inputStreamUrl;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup('test');
        $vfsStreamUrl = vfsStream::url('test');

        // Setup directories
        $this->outputStreamUrl = "$vfsStreamUrl/output";  // Dir to which we will try to copy our files
        mkdir($this->outputStreamUrl);

        $this->inputStreamUrl = "$vfsStreamUrl/input";    // Dir from which we will try to copy our files
        mkdir($this->inputStreamUrl);

        // Get Directory manager
        $this->directoryManager =  new DirectoryManager(
            $this->outputStreamUrl,
            new FileNamesHandler(),
            0
        );

        file_put_contents("$this->outputStreamUrl/file1", 'file1');
        file_put_contents("$this->inputStreamUrl/file1", 'file1');

        file_put_contents("$this->outputStreamUrl/file2", 'file2');
        file_put_contents("$this->outputStreamUrl/file2(0)", 'file2');

        file_put_contents("$this->inputStreamUrl/file2", 'file2');

        file_put_contents("$this->outputStreamUrl/file3", 'file3bis');
        file_put_contents("$this->outputStreamUrl/file3(0)", 'file3bis');

        file_put_contents("$this->inputStreamUrl/file3", 'file3');
    }

    /**
     * @return void
     * @dataProvider nextFileProvider
     * @throws Exception
     */
    public function testGetNextAvailableFilename(
        string $filename,
        ?string $desiredFilename,
        string $expectedFilename
    ) {
        $splFileInfo = new SplFileInfo("$this->inputStreamUrl/$filename");

        static::assertEquals(
            $expectedFilename,
            $this->directoryManager->getAvailableFilenameForFile($splFileInfo, $desiredFilename)->getFilename()
        );
    }

    /**
     * @return array[]
     */
    public function nextFileProvider(): array
    {
        return [
            ['file','file', 'file'], // TODO : file doesn't exist... Should we test first if files exists ?
            ['file2','file2', 'file2'],
            ['file3','file3', 'file3(1)'],
            ['file3', 'file4', 'file4'],
        ];
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testMoveFile()
    {
        $path = "$this->inputStreamUrl/file3";
        $expectedFileContent = file_get_contents($path);
        $expectedName = 'file3(1)';

        // Test avant Déplacement
        static::assertFalse(file_exists("$this->inputStreamUrl/$expectedName"));

        $outputFile = $this->directoryManager->moveFileInDirWithRename(
            new SplFileInfo($path),
            'file3'
        );
        // Tests après déplacement
        // Le fichier d'origine est bien supprimé
        static::assertFalse(file_exists("$this->inputStreamUrl/file3"));
        // Un nouveau fichier est bien créé
        static::assertTrue(file_exists("$this->outputStreamUrl/$expectedName"));
        // Le contenu est ok
        static::assertEquals(
            $expectedFileContent,
            file_get_contents("$this->outputStreamUrl/$expectedName")
        );
        // Le nom du fichier obtenu est ok :
        static::assertEquals($expectedName, $outputFile->getFilename());
        // Le répertoire du fichier obtenu est ok :
        static::assertEquals($this->outputStreamUrl, $outputFile->getPath());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testMoveFileDifferentName()
    {
        $path = "$this->inputStreamUrl/file3";
        $expectedFileContent = file_get_contents($path);
        $expectedName = 'file4';

        // Test avant Déplacement
        static::assertFalse(file_exists("$this->inputStreamUrl/$expectedName"));

        $outputFile = $this->directoryManager->moveFileInDirWithRename(new SplFileInfo($path), 'file4');
        // Tests après déplacement
        // Le fichier d'origine est bien supprimé
        static::assertFalse(file_exists("$this->inputStreamUrl/file3"));
        // Un nouveau fichier est bien créé
        scandir($this->outputStreamUrl);
        static::assertTrue(file_exists("$this->outputStreamUrl/$expectedName"));
        // Le contenu est ok
        static::assertEquals(
            $expectedFileContent,
            file_get_contents("$this->outputStreamUrl/$expectedName")
        );
        // Le nom du fichier obtenu est ok :
        static::assertEquals($expectedName, $outputFile->getFilename());
        // Le répertoire du fichier obtenu est ok :
        static::assertEquals($this->outputStreamUrl, $outputFile->getPath());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testMoveFileInDirNonExistingDirectory()
    {
        $directoryManager = new DirectoryManager(
            "/not/a/real/directory/hopefully",
            new FileNamesHandler()
        );
        $inputFilename = uniqid('/tmp/test_', true) . '.tst';
        file_put_contents($inputFilename, "test");

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible de renommer $inputFilename en /not/a/real/directory/hopefully/file4");
        $directoryManager->moveFileInDirWithRename(new SplFileInfo($inputFilename), 'file4');
        unlink($inputFilename);
    }


    /**
     * @return void
     * @throws Exception
     */
    public function testTooManyFiles()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unable to find a filename for file3 in $this->outputStreamUrl");
        $this->directoryManager->getAvailableFilenameForFile(
            new SplFileInfo("$this->inputStreamUrl/file3"),
            'file3',
            1
        );
    }

    /**
     * @return void
     * @dataProvider contentAndOutputName
     */
    public function testFileExistenceAndContent(string $inputName, string $outputName, bool $expected)
    {
        $file = new SplFileInfo("$this->inputStreamUrl/$inputName");
        static::assertEquals(
            $expected,
            $this->directoryManager->fileWithOutputNameExistsAndHasSameContent($file, $outputName)
        );
    }

    /**
     * @return array[]
     */
    public function contentAndOutputName(): array
    {

        return [
            ['file1', 'file1',true],     // file1 existe dans output, même contenu que le file1 dans input
            ['file1', 'file2',false],    // file2 existe dans output, pas le même contenu que le file1 dans input
            ['file1', 'nofile',false],    // nofile n'existe pas dans output
        ];
    }

    /**
     * @param string $originFile
     * @param string $outputFileName
     * @param string $errorMessage
     * @return void
     * @throws Exception
     * @dataProvider moveFileWithError
     */
    public function testMoveFileInDirErrors(string $originFilePath, string $outputFileName, string $errorMessage)
    {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage($errorMessage);
            $originFile = new SplFileInfo("$this->inputStreamUrl/$originFilePath");
            $this->directoryManager->moveFileInDir($originFile, $outputFileName);
    }

    /**
     * @return array
     */
    public function moveFileWithError(): array
    {
        return [
            [ "file3", "file1", "Fichier file1 déjà existant dans " ],
            [ "inexisting file", "whatever", " n'existe pas"]
        ];
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckDirectoryNotCreated()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("notadirectory n'est pas un répertoire");

        $directoryManager = new DirectoryManager(
            "notadirectory",
            new FileNamesHandler()
        );
        $directoryManager->check();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckDirectoryNotEnoughSpace()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Il ne reste pas assez d'espace sur le disque pour créer de fichier");

        $directoryManager = new DirectoryManager(
            "/tmp",
            new FileNamesHandler(),
            10 * (int)disk_free_space("/tmp/")
        );
        $directoryManager->check();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckDirectoryEnoughSpace()
    {
        $neededDiskFreeSpace = 0;
        $directoryManager = new DirectoryManager(
            "/tmp",
            new FileNamesHandler(),
            $neededDiskFreeSpace
        );
        $directoryManager->check();
        static::assertTrue(true);
    }
}
