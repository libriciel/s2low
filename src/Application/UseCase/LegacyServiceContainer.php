<?php
namespace S2low\Application\UseCase;

use S2low\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LegacyServiceContainer
{
    public static function getServiceContainer(): ContainerInterface
    {
        // Charger l'autoload de Symfony
        require_once __DIR__ . '/../../../vendor/autoload.php';

        // Charger les variables d'environnement
        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__ . '/../../../.env');

        // Charger le Kernel Symfony
        $kernel = new Kernel($_ENV['APP_ENV'] ?? 'dev', $_ENV['APP_DEBUG'] ?? true);
        $kernel->boot();

        // Retourner le conteneur de service
        return $kernel->getContainer();
    }
}
