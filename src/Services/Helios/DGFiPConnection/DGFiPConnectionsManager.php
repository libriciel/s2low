<?php

namespace S2low\Services\Helios\DGFiPConnection;

/**
 * Gère les deux configurations de connections possibles :
 * 1/ la configuration non PASSTRANS
 * 2/ la configuration PASSTRANS
 */
class DGFiPConnectionsManager
{
    private string $host;
    private int $port;
    private string $login;
    private string $password;
    private bool $isPassiveMode;
    private string $passtrans_server;
    private string $passtrans_port;
    private string $passtrans_login;
    private string $passtrans_password;
    private string $passtrans_sending_destination;
    private string $passtrans_response_server_path;
    private string $helios_ftp_p_appli;
    private string $ftp_connection_mode;
    private string $helios_ftp_response_server_path;
    private string $helios_sending_destination;
    private string $passtrans_connection_mode;
    private bool $passtrans_isPassiveMode;
    /**
     * @var \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder
     */
    private DGFiPConnectionBuilder $fullConfigurationBuilder;

    /**
     * @param string $helios_ftp_p_appli
     * @param string $helios_ftp_server
     * @param int $helios_ftp_port
     * @param string $helios_ftp_login
     * @param string $helios_ftp_password
     * @param string $helios_ftp_response_server_path
     * @param string $helios_sending_destination
     * @param string $helios_ftp_connection_mode
     * @param bool $helios_ftp_passive_mode
     * @param string $helios_passtrans_server
     * @param string $helios_passtrans_port
     * @param string $helios_passtrans_login
     * @param string $helios_passtrans_password
     * @param string $helios_passtrans_connection_mode
     * @param bool $helios_passtrans_passive_mode
     * @param string $helios_passtrans_sending_destination
     * @param string $helios_passtrans_response_server_path
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder $fullConfigurationBuilder
     */
    public function __construct(
        string $helios_ftp_p_appli,
        string $helios_ftp_server,
        int $helios_ftp_port,
        string $helios_ftp_login,
        string $helios_ftp_password,
        string $helios_ftp_response_server_path,
        string $helios_sending_destination,
        string $helios_ftp_connection_mode,
        bool $helios_ftp_passive_mode,
        string $helios_passtrans_server,
        int $helios_passtrans_port,
        string $helios_passtrans_login,
        string $helios_passtrans_password,
        string $helios_passtrans_connection_mode,
        bool $helios_passtrans_passive_mode,
        string $helios_passtrans_sending_destination,
        string $helios_passtrans_response_server_path,
        DGFiPConnectionBuilder $fullConfigurationBuilder
    ) {
        $this->helios_ftp_p_appli = $helios_ftp_p_appli;
        $this->host = $helios_ftp_server;
        $this->port = $helios_ftp_port;
        $this->login = $helios_ftp_login;
        $this->password = $helios_ftp_password;
        $this->ftp_connection_mode = $helios_ftp_connection_mode;
        $this->isPassiveMode = $helios_ftp_passive_mode;
        $this->helios_ftp_response_server_path = $helios_ftp_response_server_path;
        $this->helios_sending_destination = $helios_sending_destination;
        $this->passtrans_server = $helios_passtrans_server;
        $this->passtrans_port = $helios_passtrans_port;
        $this->passtrans_login = $helios_passtrans_login;
        $this->passtrans_password = $helios_passtrans_password;
        $this->passtrans_connection_mode = $helios_passtrans_connection_mode;
        $this->passtrans_isPassiveMode = $helios_passtrans_passive_mode;
        $this->passtrans_sending_destination = $helios_passtrans_sending_destination;
        $this->passtrans_response_server_path = $helios_passtrans_response_server_path;
        $this->fullConfigurationBuilder = $fullConfigurationBuilder;
    }

    /**
     * @param bool $usePasstrans
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration
     */
    public function getDGFipConnectionConfiguration(bool $usePasstrans): DGFiPConnectionConfiguration
    {
        if (! $usePasstrans) {
            return new DGFiPConnectionConfiguration(
                $this->host,
                $this->port,
                $this->login,
                $this->password,
                $this->ftp_connection_mode,
                $this->isPassiveMode,
                $this->helios_sending_destination,
                $this->helios_ftp_response_server_path,
                $this->helios_ftp_p_appli
            );
        }
        return new DGFiPConnectionConfiguration(
            $this->passtrans_server,
            $this->passtrans_port,
            $this->passtrans_login,
            $this->passtrans_password,
            $this->passtrans_connection_mode,
            $this->passtrans_isPassiveMode,
            $this->passtrans_sending_destination,
            $this->passtrans_response_server_path,
            $this->helios_ftp_p_appli
        );
    }

    /**
     * @param bool $usePasstrans
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnection
     */
    public function get(bool $usePasstrans): DGFiPConnection
    {
        return $this->getFromConfiguration(
            $this->getDGFipConnectionConfiguration($usePasstrans)
        );
    }

    /**
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration $configuration
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnection
     */
    public function getFromConfiguration(DGFiPConnectionConfiguration $configuration): DGFiPConnection
    {
        return $this->fullConfigurationBuilder->get($configuration);
    }
}
