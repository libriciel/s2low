<?php

namespace S2low\Services\Helios;

use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager;
use S2lowLegacy\Class\S2lowLogger;

/**
 * Permet de genérer des FTPHeliosReceiver configurées pour se connecter en utilisant la configuration Passtrans
 * ou non Passtrans
 */
class FTPHeliosReceiverFactory
{
    /**
     * @var \S2lowLegacy\Class\S2lowLogger
     */
    private S2lowLogger $s2lowLogger;

    private mixed $localPath;
    /**
     * @var \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager
     */
    private DGFiPConnectionsManager $connectionsConfigurationManager;


    public function __construct(
        S2lowLogger $s2lowLogger,
        DGFiPConnectionsManager $connectionsConfigurationManager,
        $helios_ftp_response_tmp_local_path
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->connectionsConfigurationManager = $connectionsConfigurationManager;
        $this->localPath = $helios_ftp_response_tmp_local_path;
    }

    public function get(bool $usePasstrans)
    {

        return new FTPHeliosReceiver(
            $this->s2lowLogger,
            $this->connectionsConfigurationManager->get($usePasstrans),
            $this->localPath
        );
    }
}
