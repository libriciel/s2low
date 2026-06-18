<?php

declare(strict_types=1);

namespace S2low\Tests\Legacy;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use S2low\Legacy\LegacyClassLoader;
use S2low\Legacy\LegacyRouteLoader;

class LegacyRouteLoaderTests extends TestCase
{
    private LegacyRouteLoader $legacyRouteLoader;

    /**
     * @param array $directory
     * @return void
     */
    private function setUpVFSAndLegacyRoadLoader(array $directory): void
    {
        $file_system = vfsStream::setup('/tmp', 444, ['testDirectory' => $directory]);
        $this->legacyRouteLoader = new LegacyRouteLoader(
            $file_system->url(),
            '/testDirectory',
            new LegacyClassLoader()
        );
    }

    /**
     * @param array $directory
     * @param int $numberOfRoutes
     * @return void
     * @dataProvider directoriesProvider
     * @throws \Exception
     */
    public function testFindOneRoute(array $directory, int $numberOfRoutes): void
    {
        $this->setUpVFSAndLegacyRoadLoader($directory);
        $collection = $this->legacyRouteLoader->load(null);
        static::assertSame($numberOfRoutes, $collection->count());
    }

    public function directoriesProvider(): array
    {
        return [
            [
                [
                    'test.php' => "<?php echo \"test\";?>"
                ],
                2   // Only one file => one route with one slash one route with multiple slash
            ],
            [
                [
                    'secondDirectory' => [
                        'test2.php' => "<?php echo \"test2\";?>",
                        'fileToExclude' => "I should'nt be here"
                    ],
                    'test.php' => "<?php echo \"test\";?>"
                ],
                4   // Three files, two routes for the files : the file without php extension shouldn't be taken into account
            ],
            [
                [
                    'secondDirectory' => [
                        'index.php' => "<?php echo \"I should be retrieved thrice\";?>",
                        'toRetrieve2.php' => 'I should be retrieved twice'
                    ],
                    'index.php' => "<?php echo \"I shouldn't be retrieved\";?>",
                    'toRetrieve.php' => "<?php echo \"I should be retrieved twice\";?>",
                ],
                7 // Four files, Seven routes
            ],
            [
                [
                    'secondDirectory' => [
                        'index.php' => "<?php echo \"I should be retrieved\";?>",
                    ]
                ],
                3 // One file, Three routes ( 3 for secondDirectory/index.php)
            ]

        ];
    }

    /**
     * @throws \Exception
     */
    public function testSimpleCollection()
    {
        $directory = [
            'test.php' => "<?php echo \"test\";?>"
        ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        static::assertSame(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => 'test.php',
                'legacyScript' => 'vfs://tmp/testDirectory/test.php'],
            $collection->get('app_legacy_testdoubleslash')->getDefaults(),
        );
    }

    /**
     * @throws \Exception
     */
    public function testSimpleCollectionSoubleSlash()
    {
        $directory = [
            'test.php' => "<?php echo \"test\";?>"
        ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        static::assertSame(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => 'test.php',
                'legacyScript' => 'vfs://tmp/testDirectory/test.php'],
            $collection->get('app_legacy_test')->getDefaults(),
        );
    }

    /**
     * @throws \Exception
     */
    public function testSimpleIndex()
    {
        $directory =
            [
                'secondDirectory' => [
                    'index.php' => "<?php echo \"I should be retrieved\";?>",
                ]
            ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        static::assertSame(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => 'secondDirectory/index.php',
                'legacyScript' => 'vfs://tmp/testDirectory/secondDirectory/index.php'],
            $collection->get('app_legacy_secondDirectory_index')->getDefaults(),
        );
    }

    public function testIndexesInComplexIndexStructure()
    {
        $directory = [
            'secondDirectory' => [
                'index.php' => "<?php echo \"I should be retrieved\";?>",
                'toRetrieve2.php' => 'I should be retrieved',
                'thirdDirectory' => [
                    'index.php' => "<?php echo \"I should be retrieved\";?>"
                ]
            ],
            'index.php' => "<?php echo \"I shouldn't be retrieved\";?>",
            'toRetrieve.php' => "<?php echo \"I should be retrieved\";?>",
        ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        static::assertSame(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => 'secondDirectory/index.php',
                'legacyScript' => 'vfs://tmp/testDirectory/secondDirectory/index.php'],
            $collection->get('app_legacy_secondDirectory_index')->getDefaults(),
        );
        static::assertSame(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => 'secondDirectory/thirdDirectory/index.php',
                'legacyScript' => 'vfs://tmp/testDirectory/secondDirectory/thirdDirectory/index.php'],
            $collection->get('app_legacy_secondDirectory_thirdDirectory_index')->getDefaults(),
        );
    }

    public function testSupportsLegacyRouteAndNothingElse(): void
    {
        $legacyRouteLoader = new LegacyRouteLoader('', '', new LegacyClassLoader());
        static::assertTrue($legacyRouteLoader->supports(null, 'legacyroute'));
        static::assertFalse($legacyRouteLoader->supports(null, 'randomroute'));
    }

    /**
     * @throws \Exception
     */
    public function testWithClass(): void
    {
        $directory = [
            'test.php' => file_get_contents(__DIR__ . '/fixtures/TestClass.php'),
        ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        static::assertSame(
            [
                '_controller' => 'S2low\Tests\Legacy\fixtures\TestClass::generateResponse',
                'requestPath' => 'test.php'
            ],
            $collection->get('app_legacy_testdoubleslash')->getDefaults(),
        );
    }
}
