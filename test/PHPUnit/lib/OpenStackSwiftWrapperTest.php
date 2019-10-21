<?php

class OpenStackSwiftWrapperTest extends PHPUnit_Framework_TestCase {

    /** @var  OpenStackSwiftWrapper */
    private $openStackSwiftWrapper;

    public function setUp() : void {

    	$logger = new Monolog\Logger("PHPUNIT");
    	$logger->pushHandler(new Monolog\Handler\NullHandler());

    	$content =
			$this->getMockBuilder(\Guzzle\Http\EntityBody::class)
				->disableOriginalConstructor()
				->getMock();

    	$dataObject =
			$this->getMockBuilder(\OpenCloud\ObjectStore\Resource\DataObject::class)
				->disableOriginalConstructor()
				->getMock();

    	$dataObject

			->method("getContent")
			->willReturn($content);

        $container =
            $this->getMockBuilder("OpenCloud\ObjectStore\Resource\Container")
                ->disableOriginalConstructor()
                ->getMock();

		$container

			->method("getObject")
			->willReturn($dataObject);

        $service =
            $this->getMockBuilder("\OpenCloud\ObjectStore\Service")
                ->disableOriginalConstructor()
                ->getMock();

        $service

            ->method("getContainer")
            ->willReturn($container);

        $openStack =
            $this->getMockBuilder("\OpenCloud\OpenStack")
                ->disableOriginalConstructor()
                ->getMock();

        $openStack

            ->method("objectStoreService")
            ->willReturn($service);

        $openStackFactory =
            $this->getMockBuilder("OpenStackFactory")
                ->disableOriginalConstructor()
                ->getMock();

        $openStackFactory

            ->method("getInstance")
            ->willReturn($openStack);


        /** @var OpenStackFactory $openStackFactory */
        $this->openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackFactory,
			$logger
        );

    }

	/**
	 * @throws Exception
	 */
    public function testSendFile(){
        $this->openStackSwiftWrapper->sendFile(
            "container_test",
            __DIR__."/fixtures/test.xml"
        );
		$this->noAssertion();
    }

    public function testDeleteFile(){
        $this->openStackSwiftWrapper->deleteFile(
            "container_test",
            __DIR__."/fixtures/test.xml"
        );
        $this->noAssertion();
    }

    public function testRetrieveFileLocal(){
        $filepath = __DIR__."/fixtures/test.xml";
        $this->assertEquals(
            $filepath,
            $this->openStackSwiftWrapper->retrieveFile(
                "test",
                $filepath
            )
        );
    }

    public function testRetrieveFile(){

		$this->openStackSwiftWrapper->retrieveFile(
			"test",
			"/tmp/toto"
		);
		$this->noAssertion();
	}


}