<?php

namespace S2low\Services\Helios;

use S2low\Services\Helios\FTPConnection\ConnectionConfiguration;
use S2low\Services\Helios\FTPConnection\FullConfiguration;

class FTPHeliosSenderFactory
{
    /**
     * @var \S2low\Services\Helios\HeliosConnectionBuilder
     */
    private HeliosConnectionBuilder $heliosConnectionBuilder;
    /**
     * @var \S2low\Services\Helios\HeliosConnectionsConfigurationManager
     */

    public function __construct(
        HeliosConnectionBuilder $heliosConnectionBuilder,
        $helios_ftp_p_appli
    ) {
        $this->heliosConnectionBuilder = $heliosConnectionBuilder;
        $this->pAppli = $helios_ftp_p_appli;
    }
    public function get(FullConfiguration $configuration): FTPHeliosSender
    {
        return new FTPHeliosSender(
            $this->heliosConnectionBuilder,
            $configuration,
            $this->pAppli,
        );
    }
}
