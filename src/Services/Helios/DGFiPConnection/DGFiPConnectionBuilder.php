<?php

namespace S2low\Services\Helios\DGFiPConnection;

use S2low\Services\Helios\DGFiPConnection\Protocols\FtpServiceWrapper;
use S2low\Services\Helios\DGFiPConnection\Protocols\SftpServiceWrapper;
use S2lowLegacy\Class\S2lowLogger;

/**
 * Permet d'instancier un objet DGFiPConnection
 */
class DGFiPConnectionBuilder
{
    /**
     * @var FtpServiceWrapper
     */
    private FtpServiceWrapper $ftpServiceWrapper;
    /**
     * @var SftpServiceWrapper
     */
    private SftpServiceWrapper $sftpServiceWrapper;
    /**
     * @var \S2lowLegacy\Class\S2lowLogger
     */
    private S2lowLogger $s2lowLogger;

    /**
     * @param \S2low\Services\Helios\DGFiPConnection\Protocols\FtpServiceWrapper $ftpServiceWrapper
     * @param \S2low\Services\Helios\DGFiPConnection\Protocols\SftpServiceWrapper $sftpServiceWrapper
     * @param \S2lowLegacy\Class\S2lowLogger $s2lowLogger
     */
    public function __construct(
        FtpServiceWrapper $ftpServiceWrapper,
        SftpServiceWrapper $sftpServiceWrapper,
        S2lowLogger $s2lowLogger
    ) {
        $this->ftpServiceWrapper = $ftpServiceWrapper;
        $this->sftpServiceWrapper = $sftpServiceWrapper;
        $this->s2lowLogger = $s2lowLogger;
    }

    /**
     * @param string $server adresse ou ip du serveur auquel se connecter
     * @param string $port port du serveur auquel se connecter
     * @param string $login login de l'utilisateur
     * @param string $password password
     * @param string $connectionMode Mode de connection parmi SIMULATEUR, GATEWAY, PASSTRANS_SFTP, PASSTRANS_FTPS
     * @param bool $passiveMode
     * @param string $sendingDestination répertoire sur le FTP ou les fichiers seront envoyés
     * @param string $responseServerPath répertoire sur le FTP réponses seront recherchés
     * @param string $helios_ftp_p_appli identifiant CFT des flux HELIOS
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnection
     */
    public function get(
        string $server,
        string $port,
        string $login,
        string $password,
        string $connectionMode,
        bool $passiveMode,
        string $sendingDestination,
        string $responseServerPath,
        string $helios_ftp_p_appli
    ): DGFiPConnection {
        $mode = new DGFiPConnectionMode($connectionMode);

        if ($mode->getProtocol()->usesFTPConnection()) {
            $connection = new FTPConnection(
                $mode->getProtocol(),
                $server,
                $port,
                $login,
                $password,
                $passiveMode,
                $this->ftpServiceWrapper
            );
            $DGFiPConnector = new DGFiPConnectorOnFTP(
                $mode->getCurrentDirectory(),
                $mode->getDeleteAfterDownload(),
                $mode->getCheckFtpRawCommandsReturn(),
                $mode->isUsePasstransFTPS(),
                $mode->getModeDemoWarning(),
                $connection
            );
        } else {
            $connection = new SFTPConnection($server, $port, $login, $password, $this->sftpServiceWrapper);
            $DGFiPConnector = new DGFiPConnectorOnSFTP($connection);
        }

        return new DGFiPConnection(
            $this->s2lowLogger,
            $DGFiPConnector,
            $responseServerPath,
            $sendingDestination,
            $helios_ftp_p_appli
        );
    }
}
