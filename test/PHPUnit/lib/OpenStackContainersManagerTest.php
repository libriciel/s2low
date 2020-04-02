<?php


use PHPUnit\Framework\MockObject\MockObject;

class OpenStackContainersManagerTest extends S2lowTestCase {

    private const GET_CONTAINER_WRAPPER = "getContainerWrapper";
    private const ACTES = "actes";

    /** @var MockObject | OpenStackContainerWrapper  */
    private $openStackContainerWrapperMock;
    /** @var MockObject | OpenStackContainerWrapperFactory */
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

        $this->openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method(self::GET_CONTAINER_WRAPPER)
            ->willReturn($this->openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainersStore($this->openStackContainerWrapperFactoryMock);
        $openStackContainerManager->addConfiguration(self::ACTES,$this->openStackConfig);

        $this->expectException(UnrecoverableException::class);
        $this->expectExceptionMessage("Impossible de trouver la configuration Openstack pour UnavailableContainer");

        $openStackContainerManager->getContainerWrapper("UnavailableContainer");
    }

    /**
     * @throws UnrecoverableException
     */

    public function testExecuteOnAvailableContainer(){

        $this->openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method(self::GET_CONTAINER_WRAPPER)
            ->willReturn($this->openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainersStore($this->openStackContainerWrapperFactoryMock);
        $openStackContainerManager->addConfiguration(self::ACTES,$this->openStackConfig);

        $this->assertEquals($openStackContainerManager->getContainerWrapper(self::ACTES),
            $this->openStackContainerWrapperMock);
    }

}