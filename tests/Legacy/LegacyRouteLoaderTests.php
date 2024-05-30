<?php

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
     * @return string
     */
    private function setUpVFSAndLegacyRoadLoader(array $directory): void
    {
        $file_system = vfsStream::setup('/tmp', 444, ["testDirectory" => $directory]);
        $this->legacyRouteLoader = new LegacyRouteLoader(
            $file_system->url(),
            "/testDirectory",
            new LegacyClassLoader()
        );
        $this->fileSystem = $file_system;
    }

    /**
     * @param array $directory
     * @param int $numberOfRoutes
     * @return void
     * @dataProvider directoriesProvider
     */
    public function testFindOneRoute(array $directory, int $numberOfRoutes): void
    {
        $this->setUpVFSAndLegacyRoadLoader($directory);
        $collection = $this->legacyRouteLoader->load(null);
        $this->assertEquals($numberOfRoutes, $collection->count());
    }

    public function directoriesProvider(): array
    {
        return [
            [
                [
                    "test.php" => "<?php echo \"test\";?>"
                ],
                4   // Only one file => one route with one slash one route with multiple slash + 2 routes pour index.old.php
            ],
            [
                [
                    "secondDirectory" => [
                        "test2.php" => "<?php echo \"test2\";?>",
                        "fileToExclude" => "I should'nt be here"
                    ],
                    "test.php" => "<?php echo \"test\";?>"
                ],
                6   // Three files, two routes for the files : the file without php extension shouldn't be taken into account
                    // + 2 routes pour index.old.php
            ],
            [
                [
                    "secondDirectory" => [
                        "index.php" => "<?php echo \"I should be retrieved thrice\";?>",
                        "toRetrieve2.php" => "I should be retrieved twice"
                    ],
                    "index.php" => "<?php echo \"I shouldn't be retrieved\";?>",
                    "index.old.php" => "<?php echo \"I should be retrieved twice\";?>",
                    "toRetrieve.php" => "<?php echo \"I should be retrieved twice\";?>",
                ],
                9 // Five files, Nine routes (two for index.old.php)
            ],
            [
                [
                    "secondDirectory" => [
                        "index.php" => "<?php echo \"I should be retrieved\";?>",
                    ]
                ],
                5 // One file, Five routes ( 3 for secondDirectory/index.php two for index.old.php)
            ]

        ];
    }

    public function testSimpleCollection()
    {
        $directory = [
            "test.php" => "<?php echo \"test\";?>"
        ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        $this->assertEquals(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => "test.php",
                'legacyScript' => 'vfs://tmp/testDirectory/test.php'],
            $collection->get("app_legacy_testdoubleslash")->getDefaults(),
        );
    }

    public function testSimpleCollectionSoubleSlash()
    {
        $directory = [
            "test.php" => "<?php echo \"test\";?>"
        ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        $this->assertEquals(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => "test.php",
                'legacyScript' => 'vfs://tmp/testDirectory/test.php'],
            $collection->get("app_legacy_test")->getDefaults(),
        );
    }

    public function testSimpleIndex()
    {
        $directory =
            [
                "secondDirectory" => [
                    "index.php" => "<?php echo \"I should be retrieved\";?>",
                ]
            ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        $this->assertEquals(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => "secondDirectory/index.php",
                'legacyScript' => 'vfs://tmp/testDirectory/secondDirectory/index.php'],
            $collection->get("app_legacy_secondDirectory_index")->getDefaults(),
        );
    }

    public function testIndexesInComplexIndexStructure()
    {
        $directory = [
            "secondDirectory" => [
                "index.php" => "<?php echo \"I should be retrieved\";?>",
                "toRetrieve2.php" => "I should be retrieved",
                "thirdDirectory" => [
                    "index.php" => "<?php echo \"I should be retrieved\";?>"
                ]
            ],
            "index.php" => "<?php echo \"I shouldn't be retrieved\";?>",
            "index.old.php" => "<?php echo \"I shouldn't be retrieved\";?>",
            "toRetrieve.php" => "<?php echo \"I should be retrieved\";?>",
        ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        $this->assertEquals(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => "/index.php",
                'legacyScript' => 'vfs://tmp/testDirectory/index.old.php'],
            $collection->get("homepage")->getDefaults(),
        );

        $this->assertEquals(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => "/index.php",
                'legacyScript' => 'vfs://tmp/testDirectory/index.old.php'],
            $collection->get("homepage_full")->getDefaults(),
        );

        $this->assertEquals(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => "secondDirectory/index.php",
                'legacyScript' => 'vfs://tmp/testDirectory/secondDirectory/index.php'],
            $collection->get("app_legacy_secondDirectory_index")->getDefaults(),
        );
        $this->assertEquals(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => "secondDirectory/thirdDirectory/index.php",
                'legacyScript' => 'vfs://tmp/testDirectory/secondDirectory/thirdDirectory/index.php'],
            $collection->get("app_legacy_secondDirectory_thirdDirectory_index")->getDefaults(),
        );
    }

    public function testSupportsLegacyRouteAndNothingElse()
    {
        $legacyRouteLoader = new LegacyRouteLoader("", "", new LegacyClassLoader());
        $this->assertTrue($legacyRouteLoader->supports(null, "legacyroute"));
        $this->assertFalse($legacyRouteLoader->supports(null, "randomroute"));
    }

    public function testWithClass()
    {
        $directory = [
            'test.php' => file_get_contents(__DIR__ . '/fixtures/TestClass.php'),
        ];

        $this->setUpVFSAndLegacyRoadLoader($directory);

        $collection = $this->legacyRouteLoader->load(null);

        $this->assertEquals(
            ['_controller' => 'S2low\Tests\Legacy\fixtures\TestClass::doTheWork'],
            $collection->get('app_legacy_testdoubleslash')->getDefaults(),
        );
    }
}
