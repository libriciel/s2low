<?php

use GuzzleHttp\Psr7\Stream;
use Monolog\Logger;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use OpenStack\ObjectStore\v1\Service;
use OpenStack\OpenStack;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OpenStackSwiftWrapperTest extends TestCase {
    private const GET_OBJECT = "getObject";
    private const PATH = "/fixtures/";
    private const EXISTING_FILE_NAME = "test.xml";
    private const ABSENT_FILE_NAME = "testQuiNexistePas.xml";
    private const EXISTING_FILE_PATH = __DIR__ . self::PATH . self::EXISTING_FILE_NAME;
    private const ABSENT_FILE_PATH = __DIR__ . self::PATH . self::ABSENT_FILE_NAME;
    private const CREATE_OBJECT = "createObject";
    private const CONTAINER_TEST = "container_test";

    /** @var  OpenStackSwiftWrapper */
    private $openStackSwiftWrapper;
    /** @var Logger  */
    private $logger;
    /** @var MockObject  */
    private $content;
    /** @var MockObject  */
    private $dataObject;
    /** @var Container|MockObject $container */
    private $container;
    /** @var MockObject  */
    private $service;
    /** @var MockObject  */
    private $openStack;
    /** @var MockObject|OpenStackContainersStore $openStackFactory  */
    private $openStackFactory;

    public function setUp() : void {

        if(file_exists(self::ABSENT_FILE_PATH)){
            unlink(self::ABSENT_FILE_PATH);
        }

        $this->logger = new Monolog\Logger("PHPUNIT");
        $this->logger->pushHandler(new Monolog\Handler\NullHandler());
    }

    public function tearDown(): void
    {
        if(file_exists(self::ABSENT_FILE_PATH)){
            unlink(self::ABSENT_FILE_PATH);
        }
    }

	/**
	 * @throws Exception
	 */
    public function testSendFile(){

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->once())
            ->method('createObject')
            ->with($this->callback(
                function($args){
                    return $args['stream']->getMetadata("uri") ==  OpenStackSwiftWrapperTest::EXISTING_FILE_PATH
                        && $args["name"] = OpenStackSwiftWrapperTest::EXISTING_FILE_NAME;
                }
            ));

        $openStackContainersStore = $this->getMockBuilder(OpenStackContainersStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->with($this->equalTo(self::CONTAINER_TEST))
        ->willReturn($openStackSwiftWrapper);

        /** @var OpenStackContainersStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger
        );

        $openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::EXISTING_FILE_PATH
        );
    }

    /**
     * @throws Exception
     */
    public function testSendFileWithDifferentName(){

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->once())
            ->method('createObject')
            ->with($this->callback(
                function($args){
                    return $args['stream']->getMetadata("uri") ==  OpenStackSwiftWrapperTest::EXISTING_FILE_PATH
                        && $args["name"] = OpenStackSwiftWrapperTest::ABSENT_FILE_NAME;
                }
            ));

        $openStackContainersStore = $this->getMockBuilder(OpenStackContainersStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->with($this->equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);

        /** @var OpenStackContainersStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger
        );

        $openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::EXISTING_FILE_PATH,
            self::ABSENT_FILE_NAME
        );
    }

    /**
     * @throws Exception
     */
    public function testSendNonexistentFile(){

        $openStackContainersStore = $this->getMockBuilder(OpenStackContainersStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->never())
            ->method("getContainerWrapper");

        $this->expectException(CloudStorageException::class);

        /** @var OpenStackContainersStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger
        );

        $openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::ABSENT_FILE_PATH
        );

    }

    /**
     * @throws UnrecoverableException
     * @throws BadResponseError
     */

    public function testDeleteFile(){

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->once())
            ->method('delete')
            ->with(self::ABSENT_FILE_NAME);

        $openStackContainersStore = $this->getMockBuilder(OpenStackContainersStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->with($this->equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);


        /** @var OpenStackContainersStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger
        );

        $openStackSwiftWrapper->deleteFile(
            self::CONTAINER_TEST,
            self::ABSENT_FILE_PATH
        );
    }

    /**
     * @throws Exception
     */

    public function testRetrieveFileLocal(){

        $openStackContainersStore = $this->getMockBuilder(OpenStackContainersStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->never())
            ->method("getContainerWrapper");

        /** @var OpenStackContainersStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger
        );

        $filepath = self::EXISTING_FILE_PATH;

        $this->assertEquals(
            $filepath,
            $openStackSwiftWrapper->retrieveFile(
                self::CONTAINER_TEST,
                $filepath
            )
        );
    }

    /**
     * @throws Exception
     */

    public function testRetrieveFile(){

        /** @var OpenStackContainersStore $openStackFactory */

        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->once())
            ->method('download')
            ->with(self::ABSENT_FILE_NAME);

        $openStackContainersStore = $this->getMockBuilder(OpenStackContainersStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->with($this->equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);

        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger
        );

		$openStackSwiftWrapper->retrieveFile(
            self::CONTAINER_TEST,
			self::ABSENT_FILE_PATH
		);
	}
}