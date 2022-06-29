<?php

namespace S2low\Legacy;

use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteLoader extends \Symfony\Component\Config\Loader\Loader
{

    /**
     * @inheritDoc
     */
    public function load(mixed $resource, string $type = null) : mixed
    {
        $collection = new RouteCollection();
        $finder = new Finder();
        $finder->files()->name('*.php');
        $baseDir = __DIR__."/../../public.ssl/";
        $dirs = [
            "admin",
            "admin/authorities",
            "admin/groups",
            "admin/message",
            "admin/modules",
            "admin/services",
            "admin/users",
            "admin/utilities",
            "api",
            "common",
            "custom/templates",
            "modules/actes",
            "modules/actes/admin",
            "modules/actes/api",
            "modules/actes/applet",
            "modules/actes/class",
            "modules/helios",
            "modules/helios/admin",
            "modules/helios/api",
            "modules/helios/class",
            "modules/mail",
            "test"
        ];
        foreach ($dirs as $dir){
            $finder = new Finder();
            $finder->files()->name('*.php');
            foreach ($finder->in($baseDir.$dir) as $legacyScriptFile) {
                // This assumes all legacy files use ".php" as extension
                $this->addRouteForFile($legacyScriptFile, $dir, $collection);
            }
        }

        $files = ["ident.php","login.php","logout.php","maintenance.php","pre-requis.php"];
        $dir = "/";

        foreach ($files as $file){
            $finder = new Finder();
            $finder->files()->name($file);
            foreach ($finder->in($baseDir) as $legacyScriptFile) {
                // This assumes all legacy files use ".php" as extension
                $this->addRouteForFile($legacyScriptFile, $dir, $collection);
            }
        }

        $collection->add("homepage", new Route('/', [
            '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
            'requestPath' => '/index.php',
            'legacyScript' => "/var/www/s2low/public.ssl/index.old.php",
        ]));

        $collection->add("homepage_full", new Route('/index.php', [
            '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
            'requestPath' => '/index.php',
            'legacyScript' => "/var/www/s2low/public.ssl/index.old.php",
        ]));

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function supports(mixed $resource, string $type = null) : bool
    {
        return 'legacyroute' === $type;
    }

    /**
     * @param mixed $legacyScriptFile
     * @param string $dir
     * @param \Symfony\Component\Routing\RouteCollection $collection
     * @return void
     */
    private function addRouteForFile(mixed $legacyScriptFile, string $dir, RouteCollection $collection): void
    {
        $relativePathname = $legacyScriptFile->getRelativePathname();
        $shortFilename = basename($relativePathname, '.php');
        $routeName = sprintf('app.legacy.%s', str_replace('/', '_', $dir . "/" . $shortFilename));

        $collection->add($routeName, new Route('/' . $dir . '/' . $relativePathname, [
            '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
            'requestPath' => '/' . $dir . '/' . $relativePathname,
            'legacyScript' => $legacyScriptFile->getPathname(),
        ]));
    }
}