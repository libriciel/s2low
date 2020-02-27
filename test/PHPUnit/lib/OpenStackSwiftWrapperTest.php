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
    /** @var MockObject|OpenStackFactory $openStackFactory  */
    private $openStackFactory;

    public function setUp() : void {

        if(file_exists(self::ABSENT_FILE_PATH)){
            unlink(self::ABSENT_FILE_PATH);
        }

        $this->logger = new Monolog\Logger("PHPUNIT");
        $this->logger->pushHandler(new Monolog\Handler\NullHandler());

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
            $this->getMockBuilder(OpenStackFactory::class)
                ->disableOriginalConstructor()
                ->getMock();
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

        /** @var OpenStackFactory $openStackFactory */
        $this->openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $this->openStackFactory,
            $this->logger
        );
    }

	/**
	 * @throws Exception
	 */
    public function testSendFile(){

        $this->container
            ->expects($this->once())
            ->method(self::CREATE_OBJECT)
            ->with($this->callback(
                function($args){
                    return $args['stream']->getMetadata("uri") ==  OpenStackSwiftWrapperTest::EXISTING_FILE_PATH
                        && $args["name"] = OpenStackSwiftWrapperTest::EXISTING_FILE_NAME;
                }
            ));

        $this->linkMocks();

        $this->openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::EXISTING_FILE_PATH
        );
    }

    /**
     * @throws Exception
     */
    public function testSendFileWithDifferentName(){

        $this->container
            ->expects($this->once())
            ->method(self::CREATE_OBJECT)
            ->with($this->callback(
                function($args){
                    return $args['stream']->getMetadata("uri") === OpenStackSwiftWrapperTest::EXISTING_FILE_PATH
                        && $args["name"] === OpenStackSwiftWrapperTest::ABSENT_FILE_NAME;
                }
            ));

        $this->linkMocks();

        $this->openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::EXISTING_FILE_PATH,
            self::ABSENT_FILE_NAME
        );
    }

    /**
     * @throws Exception
     */
    public function testSendNonexistentFile(){

        $this->container
            ->expects($this->never())
            ->method(self::CREATE_OBJECT);

        $this->linkMocks();

        $this->expectException(CloudStorageException::class);

        $this->openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::ABSENT_FILE_PATH
        );

    }

    /**
     * @throws UnrecoverableException
     * @throws BadResponseError
     */

    public function testDeleteFile(){

        $this->openStackFactory
            ->expects($this->once())
            ->method("getInstance")
            ->with(self::CONTAINER_TEST)
            ->willReturn($this->openStack);

        $this->container
            ->expects($this->once())
            ->method(self::GET_OBJECT)
            ->with(self::ABSENT_FILE_NAME);

        $this->linkMocks();

        $this->openStackSwiftWrapper->deleteFile(
            self::CONTAINER_TEST,
            self::ABSENT_FILE_PATH
        );
    }

    /**
     * @throws Exception
     */

    public function testRetrieveFileLocal(){
        $this->container
            ->expects($this->never())
            ->method(self::GET_OBJECT);

        $this->linkMocks();

        $filepath = self::EXISTING_FILE_PATH;

        $this->assertEquals(
            $filepath,
            $this->openStackSwiftWrapper->retrieveFile(
                self::CONTAINER_TEST,
                $filepath
            )
        );
    }

    /**
     * @throws Exception
     */

    public function testRetrieveFile(){

        $this->container
            ->expects($this->once())
            ->method(self::GET_OBJECT)
            ->with(self::ABSENT_FILE_NAME)
            ->willReturn($this->dataObject);

        $this->linkMocks();

		$this->openStackSwiftWrapper->retrieveFile(
            self::CONTAINER_TEST,
			self::ABSENT_FILE_PATH
		);
	}
}