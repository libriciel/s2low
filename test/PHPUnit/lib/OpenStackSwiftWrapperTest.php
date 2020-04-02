<?php

use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class OpenStackSwiftWrapperTest extends TestCase {
    private const PATH = "/fixtures/";
    private const EXISTING_FILE_NAME = "test.xml";
    private const ABSENT_FILE_NAME = "testQuiNexistePas.xml";
    private const EXISTING_FILE_PATH = __DIR__ . self::PATH . self::EXISTING_FILE_NAME;
    private const ABSENT_FILE_PATH = __DIR__ . self::PATH . self::ABSENT_FILE_NAME;
    private const CONTAINER_TEST = "container_test";

    /** @var Logger  */
    private $logger;

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

        /** @var  $openStackSwiftWrapper OpenStackSwiftWrapper | PHPUnit\Framework\MockObject\MockObject*/
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

        /** @var  $openStackContainersStore OpenStackContainersStore | PHPUnit\Framework\MockObject\MockObject */
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
        /** @var  $openStackSwiftWrapper OpenStackSwiftWrapper | PHPUnit\Framework\MockObject\MockObject*/
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

        /** @var  $openStackContainersStore OpenStackContainersStore | PHPUnit\Framework\MockObject\MockObject */
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
        /** @var  $openStackContainersStore OpenStackContainersStore | PHPUnit\Framework\MockObject\MockObject */
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
     */

    public function testDeleteFile(){
        /** @var  $openStackSwiftWrapper OpenStackSwiftWrapper | PHPUnit\Framework\MockObject\MockObject*/
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->once())
            ->method('delete')
            ->with(self::ABSENT_FILE_NAME);

        /** @var  $openStackContainersStore OpenStackContainersStore | PHPUnit\Framework\MockObject\MockObject */
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
        /** @var  $openStackContainersStore OpenStackContainersStore | PHPUnit\Framework\MockObject\MockObject */
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

        /** @var  $openStackSwiftWrapper OpenStackSwiftWrapper | PHPUnit\Framework\MockObject\MockObject*/
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->once())
            ->method('download')
            ->with(self::ABSENT_FILE_NAME);

        /** @var  $openStackContainersStore OpenStackContainersStore | PHPUnit\Framework\MockObject\MockObject */
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