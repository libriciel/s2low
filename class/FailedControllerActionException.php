<?php

namespace S2lowLegacy\Class;

use Exception;

class FailedControllerActionException extends Exception
{
    private string $url;

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    public function __construct(string $message, string $url)
    {
        parent::__construct($message);
        $this->url = $url;
    }
}
