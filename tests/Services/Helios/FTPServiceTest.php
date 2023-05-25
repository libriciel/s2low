<?php

namespace S2low\Tests\Services\Helios;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnection;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionMode;
use S2low\Services\Helios\DGFiPConnection\FTPConnection;
use S2low\Services\Helios\DGFiPConnection\Protocols\FtpConnectionWrapper;
use S2low\Services\Helios\DGFiPConnection\Protocols\FtpServiceWrapper;
use S2low\Services\Helios\DGFiPConnection\Protocols\SftpServiceWrapper;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TmpFolder;
use S2lowTestCase;

/**
 *
 */
class FTPServiceTest extends S2lowTestCase
{
    /**
     * @var MockObject|FtpServiceWrapper
     */
    private FtpServiceWrapper|MockObject $ftpServiceWrapperMock;
    /**
     * @var SftpServiceWrapper|MockObject
     */
    private SftpServiceWrapper|MockObject $sftpServiceWrapperMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|(\S2low\Services\Helios\DGFiPConnection\FTPConnection&\PHPUnit\Framework\MockObject\MockObject)
     */
    private MockObject|FTPConnection $ftpConnectionMock;

    /**
     * @param int|string $dataName
     *
     * @internal This method is not covered by the backward compatibility promise for PHPUnit
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        /** @var MockObject | FtpServiceWrapper $ftpServiceWrapperMock */
        $this->ftpServiceWrapperMock = $this->getMockBuilder(FtpServiceWrapper::class)->getMock();
        $this->sftpServiceWrapperMock = $this->getMockBuilder(SftpServiceWrapper::class)->getMock();
        $this->ftpConnectionMock = $this->getMockBuilder(FtpConnectionWrapper::class)
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @param bool $isPassive
     * @param string $PasstransMode
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnection
     */
    public function getDGFiPConnection(bool $isPassive, string $PasstransMode): DGFiPConnection
    {
        return ( new DGFiPConnectionBuilder(
            $this->ftpServiceWrapperMock,
            $this->sftpServiceWrapperMock,
            $this->getObjectInstancier()->get(S2lowLogger::class)
        ) )->get(
            'helios_ftp_server',
            1024,
            'helios_ftp_login',
            'helios_ftp_password',
            $PasstransMode,
            $isPassive,
            'sending_destination',
            'response_server_path',
            'p_appli'
        );
    }

    /**
     * @return array[]
     */
    public function connectionProvider(): array
    {
        return [
            [ DGFiPConnectionMode::SIMULATEUR, 'connect'],
            [ DGFiPConnectionMode::GATEWAY, 'connect'],
            [ DGFiPConnectionMode::PASSTRANS_FTPS, 'sslConnect']
        ];
    }

    /**
     * @dataProvider connectionProvider
     * @param $heliosFtpPasstransMode
     * @param $connectFunction
     * @return void
     * @throws \Exception
     */
    public function testConnect($heliosFtpPasstransMode, $connectFunction)
    {
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method($connectFunction)
            ->with('helios_ftp_server', 1024, 90)
            ->willReturn($this->ftpConnectionMock);
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('login')
            ->with($this->ftpConnectionMock, 'helios_ftp_login', 'helios_ftp_password')
            ->willReturn(true);
        $connection = $this->getDGFiPConnection(false, $heliosFtpPasstransMode);
        $connection->connect();
    }

    public function testURL()
    {
        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::SIMULATEUR);
        $this->assertEquals(
            'FTP://helios_ftp_login:helios_ftp_password@helios_ftp_server [Actif].[MODE DEMO]',
            $connection->getURL()
        );
    }

    /**
     * @return array
     */
    public function passiveProvider(): array
    {
        return [
            [false,false],
            [true, true]
        ];
    }
    /**
     * @dataProvider passiveProvider
     * @param $isPasv
     * @return void
     * @throws \Exception
     */
    public function testSetPassive($isPasv)
    {
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('connect')
            ->with('helios_ftp_server', 1024, 90)
            ->willReturn($this->ftpConnectionMock);
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('login')
            ->with($this->ftpConnectionMock, 'helios_ftp_login', 'helios_ftp_password')
            ->willReturn(true);
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('pasv')
            ->with($this->ftpConnectionMock, $isPasv)
            ->willReturn(true);
        $connection = $this->getDGFiPConnection($isPasv, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testImpossibleToConnect()
    {
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('connect')
            ->with('helios_ftp_server', 1024, 90)
            ->willReturn(false);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Impossible de se connecter au serveur helios_ftp_server:1024');
        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testImpossibleToLogin()
    {
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('connect')
            ->with('helios_ftp_server', 1024, 90)
            ->willReturn($this->ftpConnectionMock);
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('login')
            ->with($this->ftpConnectionMock, 'helios_ftp_login', 'helios_ftp_password')
            ->willReturn(false);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Impossible de se connecter avec le login helios_ftp_login');
        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
    }

    /**
     * @return array[]
     */
    public function demoProvider(): array
    {
        return [
            [ DGFiPConnectionMode::GATEWAY, './'],
            [ DGFiPConnectionMode::SIMULATEUR, '.']
        ];
    }
    /**
     * @return void
     * @dataProvider demoProvider
     * @throws \Exception
     */
    public function testgetFileNames($heliosFtpPasstransMode, $currentDirectorySyntax)
    {
        $this->setupConnection();

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('chdir')
            ->with($this->ftpConnectionMock, 'response_server_path')
            ->willReturn(true);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('nlist')
            ->with($this->ftpConnectionMock, $currentDirectorySyntax)
            ->willReturn(['file1', 'file2']);

        $connection = $this->getDGFiPConnection(false, $heliosFtpPasstransMode);
        $connection->connect();
        $this->assertEquals(
            ['file1', 'file2'],
            $connection->getFileNames()
        );
    }

    /**
     * @return void
     */
    private function setupConnection(): void
    {
        $this->ftpServiceWrapperMock
            ->method('connect')
            ->willReturn($this->ftpConnectionMock);
        $this->ftpServiceWrapperMock
            ->method('sslConnect')
            ->willReturn($this->ftpConnectionMock);
        $this->ftpServiceWrapperMock
            ->method('login')
            ->willReturn(true);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testGetError1()
    {
        $this->setupConnection();

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('chdir')
            ->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Impossible d\'aller sur le répertoire distant response_server_path');

        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
        $connection->getFileNames();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testGetError2()
    {
        $this->setupConnection();

        $this->ftpServiceWrapperMock
            ->method('chdir')
            ->willReturn(true);

        $this->ftpServiceWrapperMock
            ->method('nlist')
            ->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Impossible de lister le contenu du répertoire distant response_server_path');

        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
        $connection->getFileNames();
    }

    public function demoProvider2()
    {
        return [
            [ DGFiPConnectionMode::SIMULATEUR , self::once()],          // En mode demo, c'est s2low qui demande la suppression du fichier
            [DGFiPConnectionMode::GATEWAY, self::never()]         // Sinon, c'est le serveur DGFip qui supprime après téléchargement
        ];
    }
    /**
     * @dataProvider demoProvider2
     * @return void
     * @throws \Exception
     */
    public function testRetrieveFile($heliosFtpPasstransMode, $numberOfDeleteCalls)
    {
        $this->setupConnection();

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($ftp, $tmp_file, $remoteFile) {
                $content = 'file contents';
                $fp = fopen("$tmp_file", 'wb');
                fwrite($fp, $content);
                fclose($fp);
                return true;
            });

        $this->ftpServiceWrapperMock    // Si on n'est pas en mode démo, on ne demande pas la destruction du fichier
            ->expects($numberOfDeleteCalls)
            ->method('delete')
            ->willReturn(true);

        $connection = $this->getDGFiPConnection(false, $heliosFtpPasstransMode);
        $connection->connect();

        $tmpFolder = new TmpFolder();
        $tmpDir = $tmpFolder->create();

        $this->assertTrue(
            $connection->retrieveFile('file', "$tmpDir/")
        );
        $this->assertEquals(
            'file contents',
            file_get_contents("$tmpDir/file")
        );

        $tmpFolder->delete($tmpDir);
    }

    public function testDisconnect()
    {
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('close')
            ->with($this->ftpConnectionMock)
            ->willReturn(true);

        $connection = $this->getDGFiPConnection(true, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
        $connection->disconnect();
    }

    public function testSendRawCommand()
    {
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::exactly(3))
            ->method('raw')
            ->withConsecutive(
                [$this->ftpConnectionMock, 'site P_DEST p_dest'],
                [$this->ftpConnectionMock, 'site P_APPLI p_appli'],
                [$this->ftpConnectionMock, 'site P_MSG p_msg']
            )
            ->willReturn([200, 'Yay']);
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("put")
            ->with($this->ftpConnectionMock, 'sending_destinationfile_to_send', 'file_to_send', FTP_BINARY)
            ->willReturn(true);

        $connection = $this->getDGFiPConnection(true, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
        $connection->sendOneFileWithProperties('p_dest', 'p_msg', 'file_to_send');
    }

    public function testSendRawCommandWithErrorDemoMode()
    {
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::exactly(3))
            ->method('raw')
            ->withConsecutive(
                [$this->ftpConnectionMock, 'site P_DEST p_dest'],
                [$this->ftpConnectionMock, 'site P_APPLI p_appli'],
                [$this->ftpConnectionMock, 'site P_MSG p_msg']
            )
            ->willReturn([400,"I'm a teapot"]); // En mode démo, l'erreur ne pertubera pas l'envoi ...

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('put')
            ->with($this->ftpConnectionMock, 'sending_destinationfile_to_send', 'file_to_send', FTP_BINARY)
            ->willReturn(true);

        $connection = $this->getDGFiPConnection(true, DGFiPConnectionMode::SIMULATEUR);
        $connection->connect();

        $connection->sendOneFileWithProperties('p_dest', 'p_msg', 'file_to_send');
    }

    public function testSendRawCommandWithError()
    {
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('raw')
            ->with($this->ftpConnectionMock, 'site P_DEST p_dest')
            ->willReturn([418,"I'm a teapot"]);

        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::GATEWAY);
        $connection->connect();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("[FAILED] Send FTP raw command
site P_DEST p_dest
********** RESULT *******
418
I'm a teapot
******** END RESULT ************");
        $connection->sendOneFileWithProperties('p_dest', 'p_msg', 'file_to_send');
    }

    public function testSendOneFile()
    {
        $this->setupConnection();

        $this->ftpServiceWrapperMock
            ->expects(self::exactly(3))
            ->method('raw')
            ->withConsecutive(
                [$this->ftpConnectionMock, 'site P_DEST p_dest'],
                [$this->ftpConnectionMock, 'site P_APPLI p_appli'],
                [$this->ftpConnectionMock, 'site P_MSG p_msg']
            )
            ->willReturn([200,"Yay"]);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("put")
            ->with($this->ftpConnectionMock, 'sending_destinationfile_path', 'file_path', FTP_BINARY)
            ->willReturn(true);

        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
        $connection->sendOneFileWithProperties('p_dest', 'p_msg', 'file_path');
    }

    public function testSendOneFileWithError()  //TODO
    {
        $this->setupConnection();

        $this->ftpServiceWrapperMock
            ->expects(self::exactly(3))
            ->method('raw')
            ->withConsecutive(
                [$this->ftpConnectionMock, 'site P_DEST p_dest'],
                [$this->ftpConnectionMock, 'site P_APPLI p_appli'],
                [$this->ftpConnectionMock, 'site P_MSG p_msg']
            )
            ->willReturn([200,"Yay"]);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('put')
            ->with($this->ftpConnectionMock, 'sending_destinationfile_path', 'file_path', FTP_BINARY)
            ->willReturn(false);


        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Erreur lors de l\'envoi du fichier file_path vers le serveur FTP');
        $connection->sendOneFileWithProperties('p_dest', 'p_msg', 'file_path');
    }

    public function testConfigureFilePropertiesPasstrans()  //TODO
    {
        //TODO : ne configurer Passtrans que lors de la création du service...
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('raw')
            ->with($this->ftpConnectionMock, 'site meta P_DEST=p_dest;P_APPLI=p_appli;P_MSG=p_msg')
            ->willReturn(['200', 'cool cool cool']);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('put')
            ->with($this->ftpConnectionMock, 'sending_destinationfile_path', 'file_path', FTP_BINARY)
            ->willReturn(true);

        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::PASSTRANS_FTPS);
        $connection->connect();
        $connection->sendOneFileWithProperties('p_dest', 'p_msg', 'file_path');
    }

    public function testConfigureFilePropertiesNotPasstrans()   //TODO
    {
        //TODO : ne configurer Passtrans que lors de la création du service...
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects($this->exactly(3))
            ->method('raw')
            ->withConsecutive([$this->ftpConnectionMock, 'site P_DEST p_dest'], [$this->ftpConnectionMock,"site P_APPLI p_appli"], [$this->ftpConnectionMock,"site P_MSG p_msg"])
            ->willReturn(['200', 'cool cool cool']);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('put')
            ->with($this->ftpConnectionMock, 'sending_destinationfile_path', 'file_path', FTP_BINARY)
            ->willReturn(true);

        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::GATEWAY);
        $connection->connect();
        $connection->sendOneFileWithProperties('p_dest', 'p_msg', 'file_path');
    }
    public function testsendFileOnUniqueConnection()
    {
        $this->setupConnection();

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('connect');
        $this->ftpServiceWrapperMock
            ->expects($this->exactly(3))
            ->method('raw')
            ->withConsecutive([$this->ftpConnectionMock, 'site P_DEST p_dest'], [$this->ftpConnectionMock,'site P_APPLI p_appli'], [$this->ftpConnectionMock,'site P_MSG p_msg'])
            ->willReturn(['200', 'cool cool cool']);
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('close')
            ->willReturn(true)
        ;
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('put')
            ->with($this->ftpConnectionMock, 'sending_destinationfile_path', 'file_path', FTP_BINARY)
            ->willReturn(true);

        $connection = $this->getDGFiPConnection(false, DGFiPConnectionMode::GATEWAY);
        $connection->sendFileOnUniqueConnection('p_dest', 'p_msg', 'file_path');
    }
}
