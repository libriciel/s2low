<?php

declare(strict_types=1);

namespace S2low\Services\Helios\DGFiPConnection;

use Psr\Log\LoggerInterface;
use S2low\Services\FilesAndDirectoriesUtils\DirectoryManagerFactory;
use S2low\Services\Helios\DGFiPConnection\Protocols\FtpServiceWrapper;
use S2low\Services\Helios\DGFiPConnection\Protocols\SftpServiceWrapper;

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
     * @var LoggerInterface
     */
    private LoggerInterface $s2lowLogger;
    /**
     * @var DirectoryManagerFactory
     */
    private DirectoryManagerFactory $directoryManagerFactory;

    /**
     * @param \S2low\Services\Helios\DGFiPConnection\Protocols\FtpServiceWrapper $ftpServiceWrapper
     * @param \S2low\Services\Helios\DGFiPConnection\Protocols\SftpServiceWrapper $sftpServiceWrapper
     * @param LoggerInterface $s2lowLogger
     * @param \S2low\Services\FilesAndDirectoriesUtils\DirectoryManagerFactory $directoryManagerFactory
     */
    public function __construct(
        FtpServiceWrapper $ftpServiceWrapper,
        SftpServiceWrapper $sftpServiceWrapper,
        LoggerInterface $s2lowLogger,
        DirectoryManagerFactory $directoryManagerFactory
    ) {
        $this->ftpServiceWrapper = $ftpServiceWrapper;
        $this->sftpServiceWrapper = $sftpServiceWrapper;
        $this->s2lowLogger = $s2lowLogger;
        $this->directoryManagerFactory = $directoryManagerFactory;
    }

    /**
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration $configuration
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnection
     */
    public function get(DGFiPConnectionConfiguration $configuration): DGFiPConnection
    {
        $DGFiPConnector = $this->getConnector($configuration);

        return new DGFiPConnection(
            $this->s2lowLogger,
            $DGFiPConnector,
            $this->directoryManagerFactory,
            $configuration->getResponseServerPath(),
            $configuration->getSendingDestination(),
            $configuration->getHeliosFtpAppli()
        );
    }

    /**
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration $configuration
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnectorOnFTP|\S2low\Services\Helios\DGFiPConnection\DGFiPConnectorOnSFTP
     */
    public function getConnector(DGFiPConnectionConfiguration $configuration): DGFiPConnectorOnSFTP|DGFiPConnectorOnFTP
    {
        if ($configuration->getMode()->getProtocol()->usesFTPConnection()) {
            $connection = new FTPConnection(
                $configuration->getMode()->getProtocol(),
                $configuration->getServer(),
                $configuration->getPort(),
                $configuration->getLogin(),
                $configuration->getPassword(),
                $configuration->isPassiveMode(),
                $this->ftpServiceWrapper
            );
            return new DGFiPConnectorOnFTP(
                $configuration->getMode()->getCurrentDirectory(),
                $configuration->getMode()->getDeleteAfterDownload(),
                $configuration->getMode()->getCheckFtpRawCommandsReturn(),
                $configuration->getMode()->isUsePasstransFTPS(),
                $configuration->getMode()->getModeDemoWarning(),
                $connection
            );
        }

        $connection = new SFTPConnection(
            $configuration->getServer(),
            $configuration->getPort(),
            $configuration->getLogin(),
            $configuration->getPassword(),
            $this->sftpServiceWrapper
        );
        return new DGFiPConnectorOnSFTP($connection);
    }
}
