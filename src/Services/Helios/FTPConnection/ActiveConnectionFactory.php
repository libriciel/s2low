<?php

namespace S2low\Services\Helios\FTPConnection;

use Exception;
use UnexpectedValueException;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Lib\FtpServiceWrapper;
use S2lowLegacy\Lib\SftpServiceWrapper;

class ActiveConnectionFactory
{
    public const TIMEOUT = 90;
    /**
     * @var \S2lowLegacy\Lib\FtpServiceWrapper
     */
    private FtpServiceWrapper $ftpServiceWrapper;
    /**
     * @var \S2lowLegacy\Lib\SftpServiceWrapper
     */
    private SftpServiceWrapper $sftpServiceWrapper;

    public function __construct(FtpServiceWrapper $ftpServiceWrapper, SftpServiceWrapper $sftpServiceWrapper, S2lowLogger $s2lowLogger)
    {
        $this->logger = $s2lowLogger;
        $this->ftpServiceWrapper = $ftpServiceWrapper;
        $this->sftpServiceWrapper = $sftpServiceWrapper;
    }
    public function get(ConnectionConfiguration $connectionConfiguration)
    {
        if ($connectionConfiguration->isFTPorFTPS()) {
            return $this->getFTPorFTPSConnection($connectionConfiguration);
        }
        $activeSFTPConnection = $this->getActiveSFTPConnection($connectionConfiguration);
        return $activeSFTPConnection;
    }

    /**
     * @param $protocol
     * @param $host
     * @param $port
     * @param $timeout
     * @param $login
     * @param $password
     * @param bool $isPassiveMode
     * @return \FTP\Connection
     * @throws \Exception
     */
    private function getFTPConnection($protocol, $host, $port, $login, $password, bool $isPassiveMode)
    {
        if ($protocol === "ftps") {
            $ftp = $this->ftpServiceWrapper->sslConnect($host, $port, self::TIMEOUT);
        } else {
            $ftp = $this->ftpServiceWrapper->connect($host, $port, self::TIMEOUT);
        }

        if (!$ftp) {
            throw new Exception("Impossible de se connecter au serveur {$host}:{$port}");
        }
        $this->logger->info("Connecté");
        if ($login) {
            $ftp_login = $this->ftpServiceWrapper->login($ftp, $login, $password);
            if (!$ftp_login) {
                throw new Exception("Impossible de se connecter avec le login {$login}");
            }
        }
        $this->ftpServiceWrapper->pasv($ftp, $isPassiveMode);
        return $ftp;
    }

    /**
     * @param \S2low\Services\Helios\FTPConnection\ConnectionConfiguration $connectionConfiguration
     * @return \S2low\Services\Helios\FTPConnection\ActiveFTPorFTPSConnection
     * @throws \Exception
     */
    private function getFTPorFTPSConnection(ConnectionConfiguration $connectionConfiguration): ActiveFTPorFTPSConnection
    {
        return new ActiveFTPorFTPSConnection(
            $this->getFTPConnection(
                $connectionConfiguration->getProtocol(),
                $connectionConfiguration->getServer(),
                $connectionConfiguration->getPort(),
                $connectionConfiguration->getLogin(),
                $connectionConfiguration->getPassword(),
                $connectionConfiguration->isPassiveMode()
            ),
            $this->ftpServiceWrapper
        );
    }

    /**
     * @param \S2low\Services\Helios\FTPConnection\ConnectionConfiguration $connectionConfiguration
     * @return \S2low\Services\Helios\FTPConnection\ActiveSFTPConnection
     * @throws \Exception
     */
    private function getActiveSFTPConnection(ConnectionConfiguration $connectionConfiguration): ActiveSFTPConnection
    {
        $connection = $this->sftpServiceWrapper->connect(
            $connectionConfiguration->getServer(),
            $connectionConfiguration->getPort()
        );

        $sftp = $this->sftpServiceWrapper->login(
            $connection,
            $connectionConfiguration->getLogin(),
            $connectionConfiguration->getPassword()
        );
        $activeSFTPConnection = new ActiveSFTPConnection($sftp, $connection, $this->sftpServiceWrapper);
        return $activeSFTPConnection;
    }
}
