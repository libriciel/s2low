<?php

namespace S2lowLegacy\Lib;

use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @deprecated Fin de l'ObjectInstancier. Il faut autowire votre service
 */
class ObjectInstancier
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function get($name): mixed
    {
        try {
            $result = $this->container->get($name);
        } catch (ServiceNotFoundException $e) {
            try {
                $result = $this->container->getParameter($name);
            } catch (Exception $secondeException) {
                throw $e;
            }
        }

        return $result;
    }

    public function getArray(array $names): array
    {
        $arrayResult = [];
        foreach ($names as $name) {
            $arrayResult[] = $this->get($name);
        }

        return $arrayResult;
    }

    public function getParameter(string $name): mixed
    {
        return $this->container->getParameter($name);
    }
}
