<?php

namespace S2low\Tests\Legacy;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use S2low\Legacy\LegacyRouteLoader;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteLoaderTests extends TestCase
{
    private LegacyRouteLoader $legacyRouteLoader;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param array $directory
     * @return string
     */
    private function setUpVFSAndLegacyRoadLoader(array $directory): void
    {
        $file_system = vfsStream::setup('/tmp', 444, ["testDirectory" => $directory]);
        $this->legacyRouteLoader = new LegacyRouteLoader($file_system->url(), "/testDirectory");
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
                3   // Only one file, only one route + 2 routes pour index.old.php
            ],
            [
                [
                    "secondDirectory" => [
                        "test2.php" => "<?php echo \"test2\";?>",
                        "fileToExclude" => "I should'nt be here"
                    ],
                    "test.php" => "<?php echo \"test\";?>"
                ],
                4   // Three files, two routes for the files : the file without php extension shouldn't be taken into account
                    // + 2 routes pour index.old.php
            ],
            [
                [
                    "secondDirectory" => [
                        "index.php" => "<?php echo \"I should be retrieved\";?>",
                        "toRetrieve2.php" => "I should be retrieved"
                    ],
                    "index.php" => "<?php echo \"I shouldn't be retrieved\";?>",
                    "index.old.php" => "<?php echo \"I should be retrieved twice\";?>",
                    "toRetrieve.php" => "<?php echo \"I should be retrieved\";?>",
                ],
                5 // Five files, Six routes (two for index.old.php)
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
            $collection->get("app_legacy_test")->getDefaults(),
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
        $legacyRouteLoader = new LegacyRouteLoader("", "");
        $this->assertTrue($legacyRouteLoader->supports(null, "legacyroute"));
        $this->assertFalse($legacyRouteLoader->supports(null, "randomroute"));
    }
}
