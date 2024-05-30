<?php

namespace S2low\Legacy;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteCollection
{
    /**
     * @var \Symfony\Component\Routing\RouteCollection
     */
    private RouteCollection $routeCollection;

    public function __construct()
    {
        $this->routeCollection = new RouteCollection();
    }

    public function getRouteollection()
    {
        return $this->routeCollection;
    }

    public function add(string $routeName, string $path, string $requestPath, string $pathName, array $routeOptions = [])
    {
        $this->routeCollection->add(
            $routeName,
            new Route(
                $path,
                [
                    '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                    'requestPath' => $requestPath,
                    'legacyScript' =>  $pathName
                ],
                $routeOptions
            )
        );
    }

    public function addClass(string $routeName, string $path, string $className, array $routeOptions = [])
    {
        $this->routeCollection->add(
            $routeName,
            new Route(
                $path,
                [
                    '_controller' => "$className::doTheWork",
                ],
                $routeOptions
            )
        );
    }
}
