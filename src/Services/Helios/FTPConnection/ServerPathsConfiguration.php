<?php

namespace S2low\Services\Helios\FTPConnection;

class ServerPathsConfiguration
{
    private string $responseServerPath;

    /**
     * @return string
     */
    public function getResponseServerPath(): string
    {
        return $this->responseServerPath;
    }
    private string $sendingDestination;

    /**
     * @return string
     */
    public function getSendingDestination(): string
    {
        return $this->sendingDestination;
    }

    public function __construct(string $responseServerPath, string $sendingDestination)
    {
        $this->responseServerPath = $responseServerPath;
        $this->sendingDestination = $sendingDestination;
    }
}
