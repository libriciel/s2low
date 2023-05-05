<?php

namespace S2low\Services\Helios\FTPConnection;

use Exception;
use UnexpectedValueException;

class ConnectionConfiguration
{
    private string $server;
    private string $port;
    private string $login;
    private string $password;
    /**
     * @var false
     */
    private mixed $protocol;

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
        return $this->isPassiveMode;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @return bool
     */
    public function isFTPorFTPS(): bool
    {
        return in_array($this->getProtocol(), ["ftp","ftps"]);
    }

    public function getPassiveModeAsString(): string
    {
        return $this->isPassiveMode ? "Passif" : "Actif";
    }

    public function getURL(): string
    {
        return "{$this->protocol}://{$this->login}:{$this->password}@{$this->server }:{$this->port} (mode {$this->getPassiveModeAsString()})";
    }
    private bool $isPassiveMode;

    public function __construct(
        string $server,
        string $port,
        string $login,
        string $password,
        bool $passive_mode,
        $protocol
    ) {
        if (!in_array($protocol, ["ftp","ftps","sftp"])) {
            throw new UnexpectedValueException("Protocole non reconnu : $protocol");
        }
        $this->server = $server;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->isPassiveMode = $passive_mode;
        $this->protocol = $protocol;
    }
}
