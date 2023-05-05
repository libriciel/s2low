<?php

namespace S2low\Services\Helios;

use S2low\Services\Helios\FTPConnection\ConnectionConfiguration;
use S2low\Services\Helios\FTPConnection\FTPServerProtocolConfiguration;
use S2low\Services\Helios\FTPConnection\FullConfiguration;
use S2low\Services\Helios\FTPConnection\FullConfigurationBuilder;
use S2low\Services\Helios\FTPConnection\ServerPathsConfiguration;
use S2low\Services\Helios\FTPConnection\SFTPServerProtocolConfiguration;
use S2lowLegacy\Class\S2lowLogger;

class HeliosConnectionsConfigurationManager
{
    /**
     * @var \S2lowLegacy\Class\S2lowLogger
     */
    private S2lowLogger $logger;
    private mixed $host;
    private mixed $port;
    private mixed $login;
    private mixed $password;
    private mixed $isPassiveMode;
    private mixed $passtrans_server;
    private mixed $passtrans_port;
    private mixed $passtrans_login;
    private mixed $passtrans_password;
    private mixed $passtrans_sending_destination;
    private mixed $passtrans_response_server_path;

    public function __construct(
        $helios_ftp_server,
        $helios_ftp_port,
        $helios_ftp_login,
        $helios_ftp_password,
        $helios_ftp_response_server_path,
        $helios_sending_destination,
        $helios_ftp_passtrans_mode,
        $helios_ftp_passive_mode,
        $helios_passtrans_server,
        $helios_passtrans_port,
        $helios_passtrans_login,
        $helios_passtrans_password,
        $helios_passtrans_passtrans_mode,
        $helios_passtrans_passive_mode,
        $helios_passtrans_sending_destination,
        $helios_passtrans_response_server_path,
        FullConfigurationBuilder $fullConfigurationBuilder
    ) {
        $this->host = $helios_ftp_server;
        $this->port = $helios_ftp_port;
        $this->login = $helios_ftp_login;
        $this->password = $helios_ftp_password;
        $this->isPstMode = $helios_ftp_passtrans_mode;
        $this->isPassiveMode = $helios_ftp_passive_mode;
        $this->helios_ftp_response_server_path = $helios_ftp_response_server_path;
        $this->helios_sending_destination = $helios_sending_destination;
        $this->passtrans_server = $helios_passtrans_server;
        $this->passtrans_port = $helios_passtrans_port;
        $this->passtrans_login = $helios_passtrans_login;
        $this->passtrans_password = $helios_passtrans_password;
        $this->passtrans_isPstMode = $helios_passtrans_passtrans_mode;
        $this->passtrans_isPassiveMode = $helios_passtrans_passive_mode;
        $this->passtrans_sending_destination = $helios_passtrans_sending_destination;
        $this->passtrans_response_server_path = $helios_passtrans_response_server_path;
        $this->fullConfigurationBuilder = $fullConfigurationBuilder;
    }

    public function get(bool $usePasstrans): FullConfiguration
    {
        if (! $usePasstrans) {
            return $this->fullConfigurationBuilder->generateConfiguration(
                $this->host,
                $this->port,
                $this->login,
                $this->password,
                $this->isPstMode,
                $this->isPassiveMode,
                $this->helios_sending_destination,
                $this->helios_ftp_response_server_path
            );
        }
        return $this->fullConfigurationBuilder->generateConfiguration(
            $this->passtrans_server,
            $this->passtrans_port,
            $this->passtrans_login,
            $this->passtrans_password,
            $this->passtrans_isPstMode,
            $this->passtrans_isPassiveMode,
            $this->passtrans_sending_destination,
            $this->passtrans_response_server_path
        );
    }
}
