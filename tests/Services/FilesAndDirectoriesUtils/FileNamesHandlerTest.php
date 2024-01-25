<?php

declare(strict_types=1);

namespace S2low\Tests\Services\FilesAndDirectoriesUtils;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use S2low\Services\FilesAndDirectoriesUtils\FileNamesHandler;

/**
 *
 */
class FileNamesHandlerTest extends TestCase
{
    private string $vfsStreamUrl;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup('test');
        $this->vfsStreamUrl = vfsStream::url('test');
    }
    /**
     * Génère le répertoire de sortie ( ou on va placer les fichiers ) et son contenu
     * @return string
     */
    private function setUpOutputDirectory(): string
    {
        $outputStreamUrl = "$this->vfsStreamUrl/output";
        mkdir($outputStreamUrl);

        file_put_contents("$outputStreamUrl/file1", 'file1');

        file_put_contents("$outputStreamUrl/file2", 'file2');
        file_put_contents("$outputStreamUrl/file2(0)", 'file2bis');

        return $outputStreamUrl;
    }

    /**
     * Génère le répertoire d'entrée ( ou on va placer les fichiers ) et son contenu
     * @return string
     */
    private function setUpInputDirectory(): string
    {
        $inputStreamUrl = "$this->vfsStreamUrl/input";
        mkdir($inputStreamUrl);

        file_put_contents("$inputStreamUrl/file1", 'file1');

        file_put_contents("$inputStreamUrl/file2", 'file2');

        file_put_contents("$inputStreamUrl/file2_bis", 'file2bis');

        return $inputStreamUrl;
    }

    /**
     * @return void
     * @dataProvider fileNamesProvider
     */
    public function testRenameFileWithSufix(string $filename, ?string $suffix, string $expected)
    {
        $fileNamesHandler = new FileNamesHandler();
        static::assertEquals(
            $expected,
            $fileNamesHandler->addSuffix($filename, $suffix)
        );
    }

    /**
     * @return array[]
     */
    public function fileNamesProvider(): array
    {
        return [
            ['file', '(0)', 'file(0)'],
            ['file', '(789)', 'file(789)'],
            ['file.xml', '(0)', 'file(0).xml'],
            ['file.xml',null,'file.xml'],
            ['/test/file.xml',null,'/test/file.xml']
            //['/test/file',0,'/test/file(0)']  TODO : rendre cohérent Hé non, on ne renvoie que le nom du fichier !!
        ];
    }

    /**
     * @param string $filename
     * @param bool $available
     * @return void
     * @dataProvider availableProvider
     */
    public function testIsAvailableFilename(string $filename, bool $available)
    {
        $fileNamesHandler = new FileNamesHandler();

        $testStreamUrl = $this->setUpOutputDirectory();

        static::assertEquals(
            $available,
            $fileNamesHandler->isAvailableFilename($testStreamUrl, $filename)
        );

        static::assertEquals(
            $available,
            $fileNamesHandler->isAvailableFilename($testStreamUrl, $filename)
        );
    }

    /**
     * @return array[]
     */
    public function availableProvider(): array
    {
        return [
            ['file',true],
            ['file1',false]
        ];
    }



    /**
     * @dataProvider samefiles
     * @param string $fileName
     * @param bool $areSame
     * @return void
     */
    public function testFilesAreTheSameThanFile1(string $fileName, bool $areSame)
    {
        $fileNamesHandler = new FileNamesHandler();

        $inputDirectory = $this->setUpInputDirectory();
        $outputDirectory = $this->setUpOutputDirectory();

        static::assertEquals(
            $areSame,
            $fileNamesHandler->areSameFilesStr("$inputDirectory/file1", "$outputDirectory/$fileName")
        );
    }

    /**
     * @return array[]
     */
    public function samefiles(): array
    {
        return [
            ['file1',true],
            ['file2',false]
        ];
    }

    /**
     * @dataProvider numbers
     * @param int $number
     * @param string|null $expected
     * @return void
     */
    public function testGetSuffix(int $number, ?string $expected): void
    {
        $fileNamesHandler = new FileNamesHandler();

        static::assertEquals(
            $expected,
            $fileNamesHandler->getSuffix($number)
        );
    }

    /**
     * @return array[]
     */
    public function numbers(): array
    {
        return [
            [0, null],
            [1, '(0)'],
            [128, '(127)']
        ];
    }
}
