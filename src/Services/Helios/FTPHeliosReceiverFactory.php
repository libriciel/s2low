<?php

namespace S2low\Services\Helios;

use S2lowLegacy\Class\S2lowLogger;

class FTPHeliosReceiverFactory
{
    /**
     * @var \S2lowLegacy\Class\S2lowLogger
     */
    private S2lowLogger $s2lowLogger;
    /**
     * @var \S2low\Services\Helios\HeliosConnectionBuilder
     */
    private HeliosConnectionBuilder $FTPService;
    private mixed $localPath;
    /**
     * @var \S2low\Services\Helios\HeliosConnectionsConfigurationManager
     */
    private HeliosConnectionsConfigurationManager $connectionsConfigurationManager;
    /**
     * @var \S2low\Services\Helios\HeliosConnectionBuilder
     */
    private HeliosConnectionBuilder $heliosConnectionBuilder;


    public function __construct(
        S2lowLogger $s2lowLogger,
        HeliosConnectionsConfigurationManager $connectionsConfigurationManager,
        HeliosConnectionBuilder $heliosConnectionBuilder,
        $helios_ftp_response_tmp_local_path
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->connectionsConfigurationManager = $connectionsConfigurationManager;
        $this->heliosConnectionBuilder = $heliosConnectionBuilder;
        $this->localPath = $helios_ftp_response_tmp_local_path;
    }

    public function get(bool $usePasstrans)
    {

        return new FTPHeliosReceiver(
            $this->s2lowLogger,
            $this->heliosConnectionBuilder->connect(
                $this->connectionsConfigurationManager->get($usePasstrans)
            ),
            $this->localPath
        );
    }
}
