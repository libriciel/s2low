<?php

namespace S2low\Legacy;

use SplFileInfo;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteLoader extends Loader
{
    /**
     * @var string
     */
    private string $legacy_ssl_path;
    private LegacyClassLoader $classLoader;

    public function __construct(
        string $project_dir,
        string $relative_legacy_ssl_path,
        LegacyClassLoader $classLoader,
        ?string $env = null,
    ) {
        $this->legacy_ssl_path = $this->trimPathToAccomodateVFS("$project_dir/$relative_legacy_ssl_path");
        $this->classLoader = $classLoader;
        parent::__construct($env);
    }

    private function trimPathToAccomodateVFS(string $path): string
    {
        return preg_replace('#(?<!vfs:)//#', '/', $path);
    }

    private function findLegacyRoutes(string $baseDirPath): Finder
    {
        $excludedFiles = [
            $this->trimPathToAccomodateVFS("$baseDirPath/index.php")
        ];
        return (new Finder())->files()
            ->in($baseDirPath)
            ->name('*.php')
            ->filter(function (SplFileInfo $file) use ($excludedFiles) {
                return !in_array($file->getPathname(), $excludedFiles);
            });
    }
    /**
     * @inheritDoc
     */
    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        $phpFilesForStandardRoutes = $this->findLegacyRoutes($this->legacy_ssl_path);

        $collection = new LegacyRouteCollection();

        foreach ($phpFilesForStandardRoutes as $phpFile) {
            $className = $this->classLoader->getClassName($phpFile->getPathname());
            if ($className != '') {
                $this->addRouteForLegacyCommand($className, $phpFile, $collection);
            } else {
                $this->addRouteForFile($phpFile, $collection);
            }
        }


        return $collection->getRouteollection();
    }

    /**
     * @inheritDoc
     */
    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'legacyroute' === $type;
    }

    private function addRouteForFile(mixed $legacyScriptFile, LegacyRouteCollection $collection): void
    {
        $relativePathname = $legacyScriptFile->getRelativePathname();
        $shortFilename = basename($relativePathname, '.php');
        $routeName = sprintf(
            'app_legacy_%s',
            ltrim(str_replace('/', '_', $legacyScriptFile->getRelativePath() . '/' . $shortFilename), '_')
        );

        $collection->add($routeName, $relativePathname, $relativePathname, $this->trimPathToAccomodateVFS($legacyScriptFile->getPathname()));
        $collection->add($routeName . 'doubleslash', '/{slash}/' . $relativePathname, $relativePathname, $this->trimPathToAccomodateVFS($legacyScriptFile->getPathname()), ['slash' => '\/?']);

        if ($shortFilename === 'index') {
            $collection->add(
                $routeName . 'index',
                preg_replace('#index\.php#', '', $relativePathname),
                $relativePathname,
                $this->trimPathToAccomodateVFS($legacyScriptFile->getPathname())
            );
        }
    }

    private function addRouteForLegacyCommand(string $className, mixed $legacyScriptFile, LegacyRouteCollection $collection): void
    {
        $relativePathname = $legacyScriptFile->getRelativePathname();
        $shortFilename = basename($relativePathname, '.php');
        $routeName = sprintf(
            'app_legacy_%s',
            ltrim(str_replace('/', '_', $legacyScriptFile->getRelativePath() . '/' . $shortFilename), '_')
        );

        $collection->addRouteWithSymfonyContainer($routeName, $relativePathname, $relativePathname, $className);
        $collection->addRouteWithSymfonyContainer($routeName . 'doubleslash', '/{slash}/' . $relativePathname, $relativePathname, $className);
    }
}
