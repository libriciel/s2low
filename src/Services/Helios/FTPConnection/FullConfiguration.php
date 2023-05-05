<?php

namespace S2low\Services\Helios\FTPConnection;

class FullConfiguration
{
    /**
     * @var \S2low\Services\Helios\FTPConnection\ConnectionConfiguration
     */
    private ConnectionConfiguration $connectionConfiguration;
    /**
     * @var \S2low\Services\Helios\FTPConnection\ServerProtocolConfiguration
     */
    private ServerProtocolConfiguration $serverProtocolConfiguration;

    /**
     * @return \S2low\Services\Helios\FTPConnection\ConnectionConfiguration
     */
    public function getConnectionConfiguration(): ConnectionConfiguration
    {
        return $this->connectionConfiguration;
    }

    /**
     * @return \S2low\Services\Helios\FTPConnection\ServerProtocolConfiguration
     */
    public function getServerProtocolConfiguration(): ServerProtocolConfiguration
    {
        return $this->serverProtocolConfiguration;
    }


    /**
     * @return \S2low\Services\Helios\FTPConnection\ServerPathsConfiguration
     */
    public function getServerPathsConfiguration(): ServerPathsConfiguration
    {
        return $this->serverPathsConfiguration;
    }

    /**
     * @var \S2low\Services\Helios\FTPConnection\ServerPathsConfiguration
     */
    private ServerPathsConfiguration $serverPathsConfiguration;

    public function __construct(
        ConnectionConfiguration $connectionConfiguration,
        ServerProtocolConfiguration $serverProtocolConfiguration,
        ServerPathsConfiguration $serverPathsConfiguration
    ) {
        $this->connectionConfiguration = $connectionConfiguration;
        $this->serverProtocolConfiguration = $serverProtocolConfiguration;
        $this->serverPathsConfiguration = $serverPathsConfiguration;
    }

    public function getDescription(): string
    {
        return "{$this->connectionConfiguration->getURL()}.{$this->serverProtocolConfiguration->getDemoModeAsString()}";
    }
}
