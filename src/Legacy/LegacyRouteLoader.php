<?php

namespace S2low\Legacy;

use SplFileInfo;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteLoader extends Loader
{
    /**
     * @var string
     */
    private string $legacy_ssl_path;

    public function __construct(string $project_dir, string $relative_legacy_ssl_path, string $env = null)
    {
        $this->legacy_ssl_path = $this->trimPathToAccomodateVFS("$project_dir/$relative_legacy_ssl_path");
        parent::__construct($env);
    }

    private function trimPathToAccomodateVFS(string $path): string
    {
        return preg_replace("#(?<!vfs:)//#", "/", $path);
    }

    private function findLegacyRoutes(string $baseDirPath): Finder
    {
        $excludedFiles = [
            $this->trimPathToAccomodateVFS("$baseDirPath/index.php"),
            $this->trimPathToAccomodateVFS("$baseDirPath/index.old.php")
            ];
        return (new Finder())->files()
            ->in($baseDirPath)
            ->name("*.php")
            ->filter(function (SplFileInfo $file) use ($excludedFiles) {
                return !in_array($file->getPathname(), $excludedFiles);
            });
    }
    /**
     * @inheritDoc
     */
    public function load(mixed $resource, string $type = null): RouteCollection
    {
        $phpFilesForStandardRoutes = $this->findLegacyRoutes($this->legacy_ssl_path);

        $collection = new RouteCollection();

        foreach ($phpFilesForStandardRoutes as $phpFile) {
                $this->addRouteForFile($phpFile, $collection);
        }

        $collection->add("homepage", new Route('/', [
            '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
            'requestPath' => '/index.php',
            'legacyScript' => $this->trimPathToAccomodateVFS("$this->legacy_ssl_path/index.old.php")
        ]));

        $collection->add("homepage_full", new Route('/index.php', [
            '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
            'requestPath' => '/index.php',
            'legacyScript' => $this->trimPathToAccomodateVFS("$this->legacy_ssl_path/index.old.php")
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

    private function addRouteForFile(mixed $legacyScriptFile, RouteCollection $collection): void
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
            'legacyScript' =>  $this->trimPathToAccomodateVFS($legacyScriptFile->getPathname())
        ]));
    }
}
