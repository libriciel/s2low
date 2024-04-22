<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Helios;

use Exception;
use PHPUnit\Framework\TestCase;
use S2low\Services\FilesAndDirectoriesUtils\DirectoryManagerFactory;
use S2low\Services\FilesAndDirectoriesUtils\FileNamesHandler;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnection;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectorOnFTP;
use S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException;
use S2lowLegacy\Class\S2lowLogger;
use SplFileInfo;

/**
 *
 */
class DGFiPConnectionTest extends TestCase
{
    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $logger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();
        $this->connector = $this->getMockBuilder(DGFiPConnectorOnFTP::class)->disableOriginalConstructor()->getMock();
        $directoryManagerFactory = new DirectoryManagerFactory(
            new FileNamesHandler()
        );
        $this->dgfipConnection = new DGFiPConnection(
            $logger,
            $this->connector,
            $directoryManagerFactory,
            'response_server_path',
            'sending_destination',
            'helios_ftp_appli'
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testRetrieveToTmpFileOk()
    {
        $this->connector->expects(static::once())->method('retrieveFile');
        $filename = '/tmp/' . uniqid('test_', true);
        file_put_contents($filename, 'content');
        $this->dgfipConnection->retrieveToTmpFile(new SplFileInfo($filename), 'file name');
        static::assertTrue(true);
        unlink($filename);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testRetrieveToTmpFileFtpFail()
    {
        $this->connector->expects(static::once())->method('retrieveFile')->willThrowException(
            new FTPFileRetrieveException("Impossible de transférer le fichier distant file vers tmp_file : ")
        );
        $filename = '/tmp/' . uniqid('test_', true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible de transférer le fichier distant file vers tmp_file : ");
        $this->dgfipConnection->retrieveToTmpFile(new SplFileInfo($filename), 'file name');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testRetrieveToTmpFileFileNotCreated()
    {
        $this->connector->expects(static::once())->method('retrieveFile');
        $filename = '/tmp/' . uniqid('test_', true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Erreur lors de la récupération de file name vers $filename : fichier non existant"
        );
        $this->dgfipConnection->retrieveToTmpFile(new SplFileInfo($filename), 'file name');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testRetrieveToTmpEmptyFile()
    {
        $this->connector->expects(static::once())->method('retrieveFile');
        $filename = '/tmp/' . uniqid('test_', true);

        file_put_contents($filename, '');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Erreur lors de la récupération de file name vers $filename : fichier vide");
        $this->dgfipConnection->retrieveToTmpFile(new SplFileInfo($filename), 'file name');
        unlink($filename);
    }
}
