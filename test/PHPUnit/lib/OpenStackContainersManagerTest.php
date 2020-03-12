<?php



class OpenStackContainersManagerTest extends S2lowTestCase {

    private const EXECUTE = "execute";
    private const GET_CONTAINER_WRAPPER = "getContainerWrapper";
    private const ACTES = "actes";
    private const FUNCTION1 = "function";
    private const OPTIONS = "options";

    private $openStackContainerWrapperMock;
    private $openStackContainerWrapperFactoryMock;
    private $openStackConfig;

    public function setUp(): void {
        $this->openStackContainerWrapperMock = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->openStackContainerWrapperFactoryMock =
            $this->getMockBuilder(OpenStackContainerWrapperFactory::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->openStackConfig = new OpenStackConfig();
        $this->openStackConfig->openstack_authentication_url_v3 = "a";
        $this->openStackConfig->openstack_username = "b";
        $this->openStackConfig->openstack_password = "c";
        $this->openStackConfig->openstack_tenant = "d";
    }

    /**
     * @throws UnrecoverableException
     */

    public function testExecuteOnUnavailableContainer(){
        $this->openStackContainerWrapperMock->expects($this->never())
            ->method(self::EXECUTE);

        $this->openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method(self::GET_CONTAINER_WRAPPER)
            ->willReturn($this->openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainersManager($this->openStackContainerWrapperFactoryMock);
        $openStackContainerManager->addConfiguration(self::ACTES,$this->openStackConfig);

        $this->expectException(UnrecoverableException::class);
        $this->expectExceptionMessage("Impossible de trouver la configuration Openstack pour UnavailableContainer");

        $openStackContainerManager->execute("UnavailableContainer", self::FUNCTION1, self::OPTIONS);
    }

	/**
	 * @throws UnrecoverableException
	 */
    public function testExecute(){
		$this->openStackContainerWrapperMock->expects($this->once())
            ->method(self::EXECUTE)
            ->with($this->equalTo(self::FUNCTION1),
                $this->equalTo(self::OPTIONS))
            ->willReturn(true);

		$this->openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method(self::GET_CONTAINER_WRAPPER)
            ->willReturn($this->openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainersManager($this->openStackContainerWrapperFactoryMock);
        $openStackContainerManager->addConfiguration(self::ACTES,$this->openStackConfig);

        $result = $openStackContainerManager->execute(self::ACTES, self::FUNCTION1, self::OPTIONS);
        $this->assertEquals(true,$result);
    }

    /**
     * @throws UnrecoverableException
     */

    public function testExecuteTwice(){
        $this->openStackContainerWrapperMock
            ->expects($this->at(0))
            ->method(self::EXECUTE)
            ->willThrowException(new Exception());

        $this->openStackContainerWrapperMock
            ->expects($this->at(1))
            ->method("resetConnection");

        $this->openStackContainerWrapperMock
            ->expects($this->at(2))
            ->method(self::EXECUTE)
            ->with($this->equalTo(self::FUNCTION1),
                $this->equalTo(self::OPTIONS));
            //->willReturn(true);

        $this->openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method(self::GET_CONTAINER_WRAPPER)
            ->willReturn($this->openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainersManager($this->openStackContainerWrapperFactoryMock);
        $openStackContainerManager->addConfiguration(self::ACTES,$this->openStackConfig);
        $result = $openStackContainerManager->execute(self::ACTES, self::FUNCTION1, self::OPTIONS);
        //$this->assertEquals(true,$result);
    }

    /**
     * @throws UnrecoverableException
     */

    public function testExecuteFailsUntilTheEnd(){
        $this->openStackContainerWrapperMock->expects($this->exactly(OpenStackContainersManager::NUMBER_OF_ATTEMPTS))
            ->method(self::EXECUTE)
            ->willThrowException(new BadMethodCallException("Exception de test"));

        $this->openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method(self::GET_CONTAINER_WRAPPER)
            ->willReturn($this->openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainersManager($this->openStackContainerWrapperFactoryMock);
        $openStackContainerManager->addConfiguration(self::ACTES,$this->openStackConfig);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Exception de test");

        $openStackContainerManager->execute(self::ACTES, self::FUNCTION1, self::OPTIONS);
    }
}