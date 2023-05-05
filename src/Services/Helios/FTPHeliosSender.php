<?php

namespace S2low\Services\Helios;

use Exception;
use S2low\Services\Helios\FTPConnection\FullConfiguration;

class FTPHeliosSender
{
    /** @var HeliosConnectionBuilder  */
    private $heliosConnectionBuilder;

    private $pAppli;

    public function __construct(
        HeliosConnectionBuilder $heliosConnectionBuilder,
        FullConfiguration $fullConfiguration,
        $helios_ftp_p_appli,
    ) {
        $this->heliosConnectionBuilder = $heliosConnectionBuilder;
        $this->fullConfiguration = $fullConfiguration;
        $this->pAppli = $helios_ftp_p_appli;
    }

    /**
     * @param bool $p_dest
     * @param string $p_msg
     * @param string $file_to_send
     * @throws Exception
     */
    public function sendFile(string $p_dest, string $p_msg, string $file_to_send): void
    {
        $heliosConnection = $this->heliosConnectionBuilder->connect(
            $this->fullConfiguration
        );
        $heliosConnection->sendOneFileWithProperties($p_dest, $p_msg, $this->pAppli, $file_to_send);
        $heliosConnection->disconnect();
    }
}
