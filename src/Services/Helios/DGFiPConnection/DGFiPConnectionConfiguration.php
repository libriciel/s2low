<?php

namespace S2low\Services\Helios\DGFiPConnection;

/**
 * Contient la configuration complète d'une connection vers un serveur DGFiP
 */
class DGFiPConnectionConfiguration
{
    private string $server;

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function isPassiveMode(): bool
    {
        return $this->passiveMode;
    }

    /**
     * @return string
     */
    public function getSendingDestination(): string
    {
        return $this->sendingDestination;
    }

    /**
     * @return string
     */
    public function getResponseServerPath(): string
    {
        return $this->responseServerPath;
    }

    /**
     * @return string
     */
    public function getHeliosFtpAppli(): string
    {
        return $this->helios_ftp_appli;
    }

    /**
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionMode
     */
    public function getMode(): DGFiPConnectionMode
    {
        return $this->mode;
    }
    private string $port;
    private string $login;
    private string $password;
    private bool $passiveMode;
    private string $sendingDestination;
    private string $responseServerPath;
    private string $helios_ftp_appli;
    /**
     * @var \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionMode
     */
    private DGFiPConnectionMode $mode;

    /**
     * @param string $server
     * @param string $port
     * @param string $login
     * @param string $password
     * @param string $connectionMode
     * @param bool $passiveMode
     * @param string $sendingDestination
     * @param string $responseServerPath
     * @param string $helios_ftp_p_appli
     */
    public function __construct(
        string $server,
        int $port,
        string $login,
        string $password,
        string $connectionMode,
        bool $passiveMode,
        string $sendingDestination,
        string $responseServerPath,
        string $helios_ftp_p_appli
    ) {
        $this->server = $server;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->passiveMode = $passiveMode;
        $this->sendingDestination = $sendingDestination;
        $this->responseServerPath = $responseServerPath;
        $this->helios_ftp_appli = $helios_ftp_p_appli;
        $this->mode = new DGFiPConnectionMode($connectionMode);
    }
}
