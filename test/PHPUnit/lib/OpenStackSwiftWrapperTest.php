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
    /** @var MockObject|OpenStackContainersManager $openStackFactory  */
    private $openStackFactory;

    public function setUp() : void {

        if(file_exists(self::ABSENT_FILE_PATH)){
            unlink(self::ABSENT_FILE_PATH);
        }

        $this->logger = new Monolog\Logger("PHPUNIT");
        $this->logger->pushHandler(new Monolog\Handler\NullHandler());
/*
        $this->content =
            $this->getMockBuilder(Stream::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->dataObject =
            $this->getMockBuilder(StorageObject::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->container =
            $this->getMockBuilder(Container::class)
                ->disableOriginalConstructor()
                //->setMethods(['createObject','getObject'])
                ->getMock();

        $this->service =
            $this->getMockBuilder(Service::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->openStack =
            $this->getMockBuilder(OpenStack::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->openStackFactory =
            $this->getMockBuilder(OpenStackContainersManager::class)
                ->disableOriginalConstructor()
                ->getMock();*/
    }

    public function tearDown(): void
    {
        if(file_exists(self::ABSENT_FILE_PATH)){
            unlink(self::ABSENT_FILE_PATH);
        }
    }

    public function linkMocks(){
        $this->dataObject
            ->method("download")
            ->willReturn($this->content);


        $this->container
            ->method(self::GET_OBJECT)
            ->willReturn($this->dataObject);

        $this->service
            ->method("getContainer")
            ->willReturn($this->container);

        $this->openStack
            ->method("objectStoreV1")
            ->willReturn($this->service);


        $this->openStackFactory
            ->method("getInstance")
            ->willReturn($this->openStack);

        $this->openStackFactory
            ->method("getOpenStackParameters")
            ->willReturn([
                "region" => "region",
                "user" => [
                    'name'=> "name",
                    'password'=> "password",
                    'domain'=> ['name'=>'Default']
                ]]);

        /** @var OpenStackContainersManager $openStackFactory */
        $this->openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $this->openStackFactory,
            $this->logger
        );
    }

	/**
	 * @throws Exception
	 */
    public function testSendFile(){

        $containerManagerMock = $this->getMockBuilder(OpenStackContainersManager::class)
            ->disableOriginalConstructor()
            ->getMock();


        $containerManagerMock
            ->expects($this->once())
            ->method("execute")
            ->with($this->equalTo(self::CONTAINER_TEST),
                $this->equalTo("createObject"),
                $this->callback(
                    function($options){
                        return $options['stream']->getMetadata("uri") === OpenStackSwiftWrapperTest::EXISTING_FILE_PATH
                        && $options["name"] === OpenStackSwiftWrapperTest::EXISTING_FILE_NAME;
                }
            ));

        /** @var OpenStackContainersManager $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $containerManagerMock,
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

        $containerManagerMock = $this->getMockBuilder(OpenStackContainersManager::class)
            ->disableOriginalConstructor()
            ->getMock();


        $containerManagerMock->expects($this->once())
        ->method("execute")
        ->with(
            $this->equalTo(self::CONTAINER_TEST),
            $this->equalTo("createObject"),
            $this->callback(function ($options){
            return (
                $options['stream']->getMetadata("uri") === OpenStackSwiftWrapperTest::EXISTING_FILE_PATH
                && $options["name"] === OpenStackSwiftWrapperTest::ABSENT_FILE_NAME
            );
        }));

        /** @var OpenStackContainersManager $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $containerManagerMock,
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

        $containerManagerMock = $this->getMockBuilder(OpenStackContainersManager::class)
            ->disableOriginalConstructor()
            ->getMock();


        $containerManagerMock->expects($this->never())
            ->method("execute");

        $this->expectException(CloudStorageException::class);

        /** @var OpenStackContainersManager $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $containerManagerMock,
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

        $containerManagerMock = $this->getMockBuilder(OpenStackContainersManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $containerManagerMock
            ->expects($this->once())
            ->method("execute")
            ->with(
                $this->equalTo(self::CONTAINER_TEST),
                $this->equalTo("delete"),
                $this->equalTo(self::ABSENT_FILE_NAME));

        /** @var OpenStackContainersManager $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $containerManagerMock,
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

        $containerManagerMock = $this->getMockBuilder(OpenStackContainersManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $containerManagerMock
            ->expects($this->never())
            ->method("execute");

        /** @var OpenStackContainersManager $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $containerManagerMock,
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

        $containerManagerMock = $this->getMockBuilder(OpenStackContainersManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $containerManagerMock
            ->expects($this->once())
            ->method("execute")
            ->with($this->equalTo(self::CONTAINER_TEST),
                    $this->equalTo("download"),
                    $this->equalTo(self::ABSENT_FILE_NAME)
            );

        /** @var OpenStackContainersManager $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $containerManagerMock,
            $this->logger
        );

		$openStackSwiftWrapper->retrieveFile(
            self::CONTAINER_TEST,
			self::ABSENT_FILE_PATH
		);
	}
}