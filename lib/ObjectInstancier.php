<?php

namespace S2lowLegacy\Lib;

use Exception;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use ReflectionClass;
use ReflectionParameter;
use S2lowLegacy\Class\actes\IActesPdf;
use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Class\HttpsConnexion;
use S2lowLegacy\Class\S2lowLogger;

class ObjectInstancier
{
    private const BASIC_CLASSES = [
        Environnement::class,
        HttpsConnexion::class,
        'html',
        SessionWrapper::class,
        OpenStackContainerStore::class,
        Logger::class,
        IActesPdf::class,
        TestHandler::class,
        'helios_use_passtrans_as_default',
        'openssl_path',
        'rgs_validca_path',
        'extended_validca_path',
        'actes_appli_trigramme',
        'actes_appli_quadrigramme'
    ];
    private $objects;

    public function __construct()
    {
        $this->objects = array(ObjectInstancier::class => $this);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function get($name): mixed
    {
        global $kernel;
        if (!in_array($name, self::BASIC_CLASSES) && $kernel !== null && $kernel->isBooted()) {
            return $kernel->getContainer()->get($name);
        }
        if (! isset($this->objects[$name])) {
            $this->objects[$name] =  $this->newInstance($name);
        }
        return $this->objects[$name];
    }

    public function getArray(array $names): array
    {
        $objects = [];
        foreach ($names as $name) {
            $objects[] = $this->get($name);
        }
        return $objects;
    }

    public function unset_object($name)
    {
        unset($this->objects[$name]);
    }

    public function set($name, $value)
    {
        $this->objects[$name] = $value;
    }

    private function newInstance($className)
    {
        $reflexionClass = new ReflectionClass($className);
        if (! $reflexionClass->hasMethod('__construct')) {
            return $reflexionClass->newInstance();
        }
        $constructor = $reflexionClass->getMethod('__construct');
        $allParameters = $constructor->getParameters();
        $param = $this->bindParameters($className, $allParameters);
        return $reflexionClass->newInstanceArgs($param);
    }

    /**
     * @throws \ReflectionException
     */
    private function bindParameters($className, array $allParameters)
    {
        $param = [];
        /** @var ReflectionParameter $parameters */
        foreach ($allParameters as $parameters) {
            $type = $parameters->getType();
            if ($type !== null && !$type->isBuiltin()) {
                $class = new ReflectionClass($type->getName());
                $param_name = $class->getName();
            } else {
                $param_name = $parameters->getName();
            }
            try {
                $bind_value = $this->$param_name;
            } catch (Exception $e) {
                //throw $e;
                //On a pas trouvé le paramètre...
            }

            if (! isset($bind_value)) {
                if ($parameters->isOptional()) {
                    return $param;
                }
                throw new Exception("Impossible d'instancier $className car le parametre {$parameters->name} est manquant");
            }
            $param[] = $bind_value;
        }
        return $param;
    }
}
