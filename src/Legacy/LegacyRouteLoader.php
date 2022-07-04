<?php

namespace S2low\Legacy;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteLoader extends Loader
{
    public function findLegacyRoutes(string $baseDirPath): array
    {
        $finder = new Finder();
        $results = [];
        foreach ($finder->files()->in($baseDirPath)->name("*.php") as $file) {
            if (!in_array($file->getRelativePathname(), ["index.php","index.old.php"])) { // Pas réussi à le faire avec
                $results[] = $file;                                                       // le finder seul ...
            }
        }
        return $results;
    }
    /**
     * @inheritDoc
     */
    public function load(mixed $resource, string $type = null): RouteCollection
    {
        $phpFilesForStandardRoutes = $this->findLegacyRoutes(__DIR__ . "/../../public.ssl/");

        $collection = new RouteCollection();

        foreach ($phpFilesForStandardRoutes as $phpFile) {
                $this->addRouteForFile($phpFile, $collection);
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
    public function supports(mixed $resource, string $type = null): bool
    {
        return 'legacyroute' === $type;
    }

    /**
     * @param mixed $legacyScriptFile
     * @param \Symfony\Component\Routing\RouteCollection $collection
     * @return void
     */
    public function addRouteForFile(mixed $legacyScriptFile, RouteCollection $collection): void
    {
        $relativePathname = $legacyScriptFile->getRelativePathname();
        $shortFilename = basename($relativePathname, '.php');
        $routeName = sprintf(
            'app_legacy_%s',
            ltrim(str_replace('/', '_', $legacyScriptFile->getRelativePath() . "/" . $shortFilename), "_")
        );

        $collection->add($routeName, new Route($relativePathname, [
            '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
            'requestPath' => $relativePathname,
            'legacyScript' => $legacyScriptFile->getPathname(),
        ]));
    }
}
