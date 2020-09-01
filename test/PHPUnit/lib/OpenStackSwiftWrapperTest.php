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

        /** @var  $openStackContainersStore OpenStackContainerStore | PHPUnit\Framework\MockObject\MockObject */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->with($this->equalTo(self::CONTAINER_TEST))
        ->willReturn($openStackSwiftWrapper);

        /** @var OpenStackContainerStore $openStackFactory */
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

        /** @var  $openStackContainersStore OpenStackContainerStore | PHPUnit\Framework\MockObject\MockObject */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->with($this->equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);

        /** @var OpenStackContainerStore $openStackFactory */
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
        /** @var  $openStackContainersStore OpenStackContainerStore | PHPUnit\Framework\MockObject\MockObject */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->never())
            ->method("getContainerWrapper");

        $this->expectException(CloudStorageException::class);

        /** @var OpenStackContainerStore $openStackFactory */
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

        /** @var  $openStackContainersStore OpenStackContainerStore | PHPUnit\Framework\MockObject\MockObject */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->with($this->equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);


        /** @var OpenStackContainerStore $openStackFactory */
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
        /** @var  $openStackContainersStore OpenStackContainerStore | PHPUnit\Framework\MockObject\MockObject */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects($this->never())
            ->method("getContainerWrapper");

        /** @var OpenStackContainerStore $openStackFactory */
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

        /** @var  $openStackContainersStore OpenStackContainerStore | PHPUnit\Framework\MockObject\MockObject */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
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

    public function testRetrieveFileWithDoubleSlash(){

        /** @var  $openStackSwiftWrapper OpenStackSwiftWrapper | PHPUnit\Framework\MockObject\MockObject*/
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->once())
            ->method('objectExists')
            ->with("//trop///de////double//////slash")
            ->willReturn(true);

        $openStackSwiftWrapper->expects($this->once())
            ->method('download')
            ->with("//trop///de////double//////slash");

        /** @var  $openStackContainersStore OpenStackContainerStore | PHPUnit\Framework\MockObject\MockObject */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
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
            "slash",
            "//trop///de////double//////slash"
        );

        if(file_exists("slash")){
            unlink("slash");
        }
    }

    public function testRetrieveFileWithDoubleSlashStoredWithoutDoubleSlash(){

        /** @var  $openStackSwiftWrapper OpenStackSwiftWrapper | PHPUnit\Framework\MockObject\MockObject*/
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->exactly(2))
            ->method('objectExists')
            ->withConsecutive(["//trop///de////double//////slash"],["/trop/de/double/slash"])
            ->will($this->onConsecutiveCalls(false,true));

        $openStackSwiftWrapper->expects($this->once())
            ->method('download')
            ->with("/trop/de/double/slash");

        /** @var  $openStackContainersStore OpenStackContainerStore | PHPUnit\Framework\MockObject\MockObject */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
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
            "slash",
            "//trop///de////double//////slash"
        );

        if(file_exists("slash")){
            unlink("slash");
        }
    }

    public function testRetrieveAbsentFileWithDoubleSlash(){

        /** @var  $openStackSwiftWrapper OpenStackSwiftWrapper | PHPUnit\Framework\MockObject\MockObject*/
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects($this->exactly(2))
            ->method('objectExists')
            ->withConsecutive(["//trop///de////double//////slash"],["/trop/de/double/slash"])
            ->will($this->onConsecutiveCalls(false,false));

        $openStackSwiftWrapper->expects($this->never())
            ->method('download');

        /** @var  $openStackContainersStore OpenStackContainerStore | PHPUnit\Framework\MockObject\MockObject */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
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

        $exceptionThrown = false;
        try{
            $openStackSwiftWrapper->retrieveFile(
                self::CONTAINER_TEST,
                "slash",
                "//trop///de////double//////slash"
            );
        }   catch (Exception $e){
            $exceptionThrown = true;
            $this->assertEquals($e->getMessage(), "/trop/de/double/slash non trouvé dans container_test");
        }

        $this->assertEquals($exceptionThrown,true);

        if(file_exists("slash")){
            unlink("slash");
        }
    }
}