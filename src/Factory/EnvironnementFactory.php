<?php

namespace S2low\Factory;

use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\SessionWrapper;

class EnvironnementFactory
{
    public function __construct(
        private readonly SessionWrapper $session,
        private readonly bool $convert_api_logins_from_iso
    ) {
    }

    public function create(): Environnement
    {
        return new Environnement(
            $_GET,
            $_POST,
            $_REQUEST,
            $this->session,
            $_SERVER,
            $this->convert_api_logins_from_iso
        );
    }
}
