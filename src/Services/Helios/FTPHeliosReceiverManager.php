<?php

namespace S2low\Services\Helios;

class FTPHeliosReceiverManager
{
    private bool $isConnected = false;
    public function __construct(private readonly FTPHeliosReceiver $receiver)
    {
    }
    public function __destruct()
    {
        $this->receiver->finTraitement();
    }
    public function get(): FTPHeliosReceiver
    {
        if ($this->isConnected) {
            $this->receiver->debutTraitement();
        }
        return $this->receiver;
    }
}
