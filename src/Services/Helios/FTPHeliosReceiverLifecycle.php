<?php

namespace S2low\Services\Helios;

class FTPHeliosReceiverLifecycle
{
    private bool $needsToStartTraitement = true;
    public function __construct(private readonly FTPHeliosReceiver $receiver)
    {
    }
    public function __destruct()
    {
        $this->receiver->finTraitement();
    }
    public function get(): FTPHeliosReceiver
    {
        if ($this->needsToStartTraitement) {
            $this->receiver->debutTraitement();
            $this->needsToStartTraitement = false;
        }
        return $this->receiver;
    }
}
