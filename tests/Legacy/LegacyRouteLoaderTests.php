<?php

namespace S2low\Tests\Legacy;

use org\bovigo\vfs\vfsStream;
use S2low\Legacy\LegacyRouteLoader;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteLoaderTests extends \PHPUnit\Framework\TestCase
{
    private $legacyRouteLoader;
    public function setUp(): void
    {
        parent::setUp();
        $this->legacyRouteLoader = new LegacyRouteLoader(null);
    }

    /**
     * @param array $directory
     * @return string
     */
    private function getAsVirtualFileSystem(array $directory): string
    {
        $file_system = vfsStream::setup('/tmp', 444, $directory);

        $baseDirPath = $file_system->url() . "/testDirectory";
        return $baseDirPath;
    }

    /**
     * @param array $directory
     * @param int $numberOfRoutes
     * @return void
     * @dataProvider directoriesProvider
     */
    public function testFindOneRoute(array $directory, int $numberOfRoutes)
    {
        $filesArray = $this->legacyRouteLoader->findLegacyRoutes(
            $this->getAsVirtualFileSystem($directory)
        );
        $this->assertEquals($numberOfRoutes, count($filesArray));
    }

    public function directoriesProvider(): array
    {
        return [
            [
                [
                    "testDirectory" => [
                        "test.php" => "<?php echo \"test\";?>"
                    ]
                ],
                1   // Only one file, only one route
            ],
            [
                [
                    "testDirectory" => [
                        "secondDirectory" => [
                            "test2.php" => "<?php echo \"test2\";?>",
                            "fileToExclude" => "I should'nt be here"
                        ],
                        "test.php" => "<?php echo \"test\";?>"
                    ]
                ],
                2   // Three files, two routes : the file without php extension shouldn't be taken into account
            ],
            [
                [
                    "testDirectory" => [
                        "secondDirectory" => [
                            "index.php" => "<?php echo \"I should be retrieved\";?>",
                            "toRetrieve2.php" => "I should be retrieved"
                        ],
                        "index.php" => "<?php echo \"I shouldn't be retrieved\";?>",
                        "index.old.php" => "<?php echo \"I shouldn't be retrieved\";?>",
                        "toRetrieve.php" => "<?php echo \"I should be retrieved\";?>",
                    ]
                ],
                3 // Five files, three routes : the index.php and index.old.php shouldn't be taken into account.
                  // The secondDirectory/index.php should
            ]

        ];
    }

    public function testSimpleCollection()
    {
        $directory = [
            "testDirectory" => [
                "test.php" => "<?php echo \"test\";?>"
            ]
        ];

        $filesArray = $this->legacyRouteLoader->findLegacyRoutes(
            $this->getAsVirtualFileSystem($directory)
        );

        $collection = new RouteCollection();
        foreach ($filesArray as $file) {
            $this->legacyRouteLoader->addRouteForFile($file, $collection);
        }
        $this->assertEquals(
            [
                '_controller' => 'S2low\Controller\LegacyController::loadLegacyScript',
                'requestPath' => "test.php",
                'legacyScript' => 'vfs://tmp/testDirectory/test.php'],
            $collection->get("app_legacy_test")->getDefaults(),
        );
    }

    public function testComplexIndexStructure()
    {
        $directory = [
            "testDirectory" => [
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
            ]
        ];
        $file_system = vfsStream::setup('/tmp', 444, $directory);

        $legacyRouteLoader = new LegacyRouteLoader(null);
        $filesArray = ($legacyRouteLoader)->findLegacyRoutes($file_system->url() . "/testDirectory");
        $collection = new RouteCollection();
        foreach ($filesArray as $file) {
            $legacyRouteLoader->addRouteForFile($file, $collection);
        }
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
}
