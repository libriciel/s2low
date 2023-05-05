<?php

namespace S2low\Tests\Services\Helios;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use S2low\Services\Helios\FTPConnection\ConnectionConfiguration;
use S2low\Services\Helios\FTPConnection\ActiveConnectionFactory;
use S2low\Services\Helios\FTPConnection\FullConfiguration;
use S2low\Services\Helios\FTPConnection\FullConfigurationBuilder;
use S2low\Services\Helios\HeliosConnectionBuilder;
use S2low\Services\Helios\HeliosConnectionsConfigurationManager;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\FtpServiceWrapper;
use S2lowLegacy\Lib\SftpServiceWrapper;
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
    }

    /**
     * @return HeliosConnectionBuilder
     */
    public function getHeliosConnectionBuilder(): HeliosConnectionBuilder
    {
        $ftpConnectionFactory = new ActiveConnectionFactory(
            $this->ftpServiceWrapperMock,
            $this->sftpServiceWrapperMock,
            $this->getObjectInstancier()->get(S2lowLogger::class),
        );
        return new HeliosConnectionBuilder(
            $this->getObjectInstancier()->get(S2lowLogger::class),
            $ftpConnectionFactory
        );
    }

    /**
     * @param bool $isPassive
     * @param string $PasstransMode
     * @return ConnectionConfiguration
     */
    public function getConnectionConfiguration(bool $isPassive, string $PasstransMode): FullConfiguration
    {
        return ( new FullConfigurationBuilder() )->generateConfiguration(
            'helios_ftp_server',
            'helios_ftp_port',
            'helios_ftp_login',
            'helios_ftp_password',
            $PasstransMode,
            $isPassive,
            'sending_destination',
            'response_server_path',
        );
    }

    /**
     * @return array[]
     */
    public function connectionProvider(): array
    {
        return [
            ['FTP_SIMULATEUR', 'connect'],
            ['FTP_GATEWAY', 'connect'],
            ['FTPS_PASSTRANS', 'sslConnect']
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
            ->with('helios_ftp_server', 'helios_ftp_port', 90)
            ->willReturn('ftp');
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('login')
            ->with('ftp', 'helios_ftp_login', 'helios_ftp_password')
            ->willReturn('login');
        $this->getHeliosConnectionBuilder()->connect(
            $this->getConnectionConfiguration(false, $heliosFtpPasstransMode)
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
            ->with('helios_ftp_server', 'helios_ftp_port', 90)
            ->willReturn('ftp');
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('login')
            ->with('ftp', 'helios_ftp_login', 'helios_ftp_password')
            ->willReturn('login');
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('pasv')
            ->with('ftp', $isPasv)
            ->willReturn('login');
        $this->getHeliosConnectionBuilder()->connect(
            $this->getConnectionConfiguration($isPasv, 'FTP_GATEWAY')
        );
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
            ->with('helios_ftp_server', 'helios_ftp_port', 90)
            ->willReturn(false);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Impossible de se connecter au serveur helios_ftp_server:helios_ftp_port');
        $this->getHeliosConnectionBuilder()->connect(
            $this->getConnectionConfiguration(false, 'FTP_GATEWAY')
        );
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
            ->with('helios_ftp_server', 'helios_ftp_port', 90)
            ->willReturn('ftp');
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('login')
            ->with('ftp', 'helios_ftp_login', 'helios_ftp_password')
            ->willReturn(false);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Impossible de se connecter avec le login helios_ftp_login');
        $this->getHeliosConnectionBuilder()->connect(
            $this->getConnectionConfiguration(false, 'FTP_GATEWAY')
        );
    }

    /**
     * @return array[]
     */
    public function demoProvider(): array
    {
        return [
            ['FTP_GATEWAY', './'],
            ['FTP_SIMULATEUR', '.']
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
            ->with('ftp', 'response_server_path')
            ->willReturn(true);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method('nlist')
            ->with('ftp', $currentDirectorySyntax)
            ->willReturn(['file1', 'file2']);

        $heliosConnectionBuilder = $this->getHeliosConnectionBuilder();
        $heliosConnection = $heliosConnectionBuilder->connect(
            $this->getConnectionConfiguration(false, $heliosFtpPasstransMode)
        );
        $this->assertEquals(
            ['file1', 'file2'],
            $heliosConnection->getFileNames()
        );
    }

    /**
     * @return void
     */
    private function setupConnection(): void
    {
        $this->ftpServiceWrapperMock
            ->method('connect')
            ->willReturn('ftp');
        $this->ftpServiceWrapperMock
            ->method('sslConnect')
            ->willReturn('ftp');
        $this->ftpServiceWrapperMock
            ->method('login')
            ->willReturn('login');
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

        $heliosConnectionBuilder = $this->getHeliosConnectionBuilder();
        $heliosConnection = $heliosConnectionBuilder->connect(
            $this->getConnectionConfiguration(false, 'FTP_GATEWAY')
        );
        $heliosConnection->getFileNames();
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

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Impossible de lister le contenu du répertoire distant response_server_path');

        $heliosConnection = $this->getHeliosConnectionBuilder()
            ->connect($this->getConnectionConfiguration(false, 'FTP_GATEWAY'));

        $heliosConnection->getFileNames();
    }

    public function demoProvider2()
    {
        return [
            ['FTP_SIMULATEUR', self::once()],          // En mode demo, c'est s2low qui demande la suppression du fichier
            ['FTP_GATEWAY', self::never()]         // Sinon, c'est le serveur DGFip qui supprime après téléchargement
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
            ->method("get")
            ->willReturnCallback(function ($ftp, $tmp_file, $remoteFile) {
                $content = 'file contents';
                $fp = fopen("$tmp_file", "wb");
                fwrite($fp, $content);
                fclose($fp);
                return true;
            });

        $this->ftpServiceWrapperMock    // Si on n'est pas en mode démo, on ne demande pas la destruction du fichier
            ->expects($numberOfDeleteCalls)
            ->method('delete')
            ->willReturn(true);

        $heliosConnection = $this->getHeliosConnectionBuilder()->connect(
            $this->getConnectionConfiguration(false, $heliosFtpPasstransMode)
        );

        $tmpFolder = new TmpFolder();
        $tmpDir = $tmpFolder->create();

        $this->assertTrue(
            $heliosConnection->retrieveFile("file", "$tmpDir/")
        );
        $this->assertEquals(
            "file contents",
            file_get_contents("$tmpDir/file")
        );

        $tmpFolder->delete($tmpDir);
    }

    public function testDisconnect()
    {
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("close")
            ->with("ftp")
            ->willReturn(true);

        $heliosConnection = $this->getHeliosConnectionBuilder()->connect(
            $this->getConnectionConfiguration(true, "FTP_GATEWAY")
        );

        $heliosConnection->disconnect();
    }

    public function testSendRawCommand()
    {
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::exactly(3))
            ->method("raw")
            ->withConsecutive(
                ["ftp", "site P_DEST p_dest"],
                ["ftp","site P_APPLI p_appli"],
                ["ftp","site P_MSG p_msg"]
            )
            ->willReturn([200,"Yay"]);
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("put")
            ->with("ftp", "sending_destinationfile_to_send", "file_to_send", FTP_BINARY)
            ->willReturn(true);

        $heliosConnection = $this->getHeliosConnectionBuilder()
            ->connect($this->getConnectionConfiguration(true, "FTP_GATEWAY"));
        $heliosConnection->sendOneFileWithProperties("p_dest", "p_msg", "p_appli", "file_to_send"); //sendRawCommand("commande de test");
    }

    public function testSendRawCommandWithErrorDemoMode()
    {
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::exactly(3))
            ->method("raw")
            ->withConsecutive(
                ["ftp", "site P_DEST p_dest"],
                ["ftp","site P_APPLI p_appli"],
                ["ftp","site P_MSG p_msg"]
            )
            ->willReturn([400,"I'm a teapot"]); // En mode démo, l'erreur ne pertubera pas l'envoi ...

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("put")
            ->with("ftp", "sending_destinationfile_to_send", "file_to_send", FTP_BINARY)
            ->willReturn(true);

        $heliosConnection = $this->getHeliosConnectionBuilder()->connect(
            $this->getConnectionConfiguration(true, "FTP_SIMULATEUR")
        );
        $heliosConnection->sendOneFileWithProperties("p_dest", "p_msg", "p_appli", "file_to_send"); //sendRawCommand("commande de test");
    }

    public function testSendRawCommandWithError()
    {
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("raw")
            ->with("ftp", "site P_DEST p_dest")
            ->willReturn([418,"I'm a teapot"]);

        $heliosConnection = $this->getHeliosConnectionBuilder()
            ->connect($this->getConnectionConfiguration(false, "FTP_GATEWAY"));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("[FAILED] Send FTP raw command
site P_DEST p_dest
********** RESULT *******
418
I'm a teapot
******** END RESULT ************");
        $heliosConnection->sendOneFileWithProperties("p_dest", "p_msg", "p_appli", "file_to_send"); //sendRawCommand("commande de test");
    }

    public function testSendOneFile()   //TODO
    {
        $this->setupConnection();

        $this->ftpServiceWrapperMock
            ->expects(self::exactly(3))
            ->method("raw")
            ->withConsecutive(
                ["ftp", "site P_DEST p_dest"],
                ["ftp","site P_APPLI p_appli"],
                ["ftp","site P_MSG p_msg"]
            )
            ->willReturn([200,"Yay"]);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("put")
            ->with("ftp", "sending_destinationfile_path", "file_path", FTP_BINARY)
            ->willReturn(true);

        $heliosConnection = $this->getHeliosConnectionBuilder()
            ->connect($this->getConnectionConfiguration(false, "FTP_GATEWAY"));
        $heliosConnection->sendOneFileWithProperties("p_dest", "p_msg", "p_appli", "file_path");
    }

    public function testSendOneFileWithError()  //TODO
    {
        $this->setupConnection();

        $this->ftpServiceWrapperMock
            ->expects(self::exactly(3))
            ->method("raw")
            ->withConsecutive(
                ["ftp", "site P_DEST p_dest"],
                ["ftp","site P_APPLI p_appli"],
                ["ftp","site P_MSG p_msg"]
            )
            ->willReturn([200,"Yay"]);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("put")
            ->with("ftp", "sending_destinationfile_path", "file_path", FTP_BINARY)
            ->willReturn(false);

        $heliosConnection = $this->getHeliosConnectionBuilder()
            ->connect($this->getConnectionConfiguration(false, "FTP_GATEWAY"));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Erreur lors de l'envoi du fichier file_path vers le serveur FTP");
        $heliosConnection->sendOneFileWithProperties("p_dest", "p_msg", "p_appli", "file_path");
    }

    public function testConfigureFilePropertiesPasstrans()  //TODO
    {
        //TODO : ne configurer Passtrans que lors de la création du service...
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("raw")
            ->with("ftp", "site meta P_DEST=p_dest;P_APPLI=p_appli;P_MSG=p_msg")
            ->willReturn(["200","cool cool cool"]);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("put")
            ->with("ftp", "sending_destinationfile_path", "file_path", FTP_BINARY)
            ->willReturn(true);

        $heliosConnection = $this->getHeliosConnectionBuilder()
            ->connect($this->getConnectionConfiguration(false, "FTPS_PASSTRANS"));
        $heliosConnection->sendOneFileWithProperties("p_dest", "p_msg", "p_appli", "file_path");
    }

    public function testConfigureFilePropertiesNotPasstrans()   //TODO
    {
        //TODO : ne configurer Passtrans que lors de la création du service...
        $this->setupConnection();
        $this->ftpServiceWrapperMock
            ->expects($this->exactly(3))
            ->method("raw")
            ->withConsecutive(["ftp","site P_DEST p_dest"], ["ftp","site P_APPLI p_appli"], ["ftp","site P_MSG p_msg"])
            ->willReturn(["200","cool cool cool"]);

        $this->ftpServiceWrapperMock
            ->expects(self::once())
            ->method("put")
            ->with("ftp", "sending_destinationfile_path", "file_path", FTP_BINARY)
            ->willReturn(true);

        $heliosConnection = $this->getHeliosConnectionBuilder()
            ->connect($this->getConnectionConfiguration(false, "FTP_GATEWAY"));
        $heliosConnection->sendOneFileWithProperties("p_dest", "p_msg", "p_appli", "file_path");
    }
}
