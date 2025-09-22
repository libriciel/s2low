<?php

declare(strict_types=1);

namespace S2low\Services\Helios;

use Psr\Log\LoggerInterface;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager;

/**
 * Permet de genérer des FTPHeliosReceiver configurées pour se connecter en utilisant la configuration Passtrans
 * ou non Passtrans
 */
class FTPHeliosReceiverFactory
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $s2lowLogger;

    private mixed $localPath;
    /**
     * @var \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager
     */
    private DGFiPConnectionsManager $connectionsConfigurationManager;
    private string $helios_responses_error_path;


    /**
     * @param LoggerInterface $s2lowLogger
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager $connectionsConfigurationManager
     * @param $helios_ftp_response_tmp_local_path
     * @param $helios_responses_error_path
     */
    public function __construct(
        LoggerInterface $s2lowLogger,
        DGFiPConnectionsManager $connectionsConfigurationManager,
        $helios_ftp_response_tmp_local_path,
        $helios_responses_error_path
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->connectionsConfigurationManager = $connectionsConfigurationManager;
        $this->localPath = $helios_ftp_response_tmp_local_path;
        $this->helios_responses_error_path = $helios_responses_error_path;
    }

    /**
     * @param bool $usePasstrans
     * @return \S2low\Services\Helios\FTPHeliosReceiver
     */
    public function get(bool $usePasstrans): FTPHeliosReceiver
    {

        return new FTPHeliosReceiver(
            $this->s2lowLogger,
            $this->connectionsConfigurationManager->get($usePasstrans),
            $this->localPath,
            $this->helios_responses_error_path
        );
    }
}
