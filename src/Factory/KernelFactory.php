<?php

namespace S2low\Factory;

use S2low\Kernel;
use Symfony\Component\Dotenv\Dotenv;

class KernelFactory
{
    public static function start(): void
    {
        require dirname(__DIR__) . '/../vendor/autoload.php';

        (new Dotenv())->bootEnv("/data/config/.env");
        new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
    }
}
