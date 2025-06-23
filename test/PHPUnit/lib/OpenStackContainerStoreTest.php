<?php

use S2lowLegacy\Lib\OpenStackConfig;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackContainerWrapper;
use S2lowLegacy\Lib\OpenStackContainerWrapperFactory;
use S2lowLegacy\Lib\UnrecoverableException;

class OpenStackContainerStoreTest extends S2lowTestCase
{
    private const GET_CONTAINER_WRAPPER = "getContainerWrapper";
    private const ACTES = "actes";

    private $openStackContainerWrapperMock;
    private $openStackContainerWrapperFactoryMock;
    private $openStackConfig;

    public function setUp(): void
    {
        parent::setUp();
        $this->openStackContainerWrapperMock = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->openStackContainerWrapperFactoryMock =
            $this->getMockBuilder(OpenStackContainerWrapperFactory::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->openStackConfig = new OpenStackConfig(
            "a",
            "b",
            "c",
            "d",
            "e",
            "f"
        );
    }

    /**
     * @throws UnrecoverableException
     */

    public function testExecuteOnUnavailableContainer()
    {
        $this->openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method(self::GET_CONTAINER_WRAPPER)
            ->willReturn($this->openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainerStore($this->openStackContainerWrapperFactoryMock);
        $openStackContainerManager->addConfiguration(self::ACTES, $this->openStackConfig);

        $this->expectException(UnrecoverableException::class);
        $this->expectExceptionMessage("Impossible de trouver la configuration Openstack pour UnavailableContainer");

        $openStackContainerManager->getContainerWrapper("UnavailableContainer");
    }

    /**
     * @throws UnrecoverableException
     */

    public function testExecuteOnAvailableContainer()
    {

        $this->openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method(self::GET_CONTAINER_WRAPPER)
            ->willReturn($this->openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainerStore($this->openStackContainerWrapperFactoryMock);
        $openStackContainerManager->addConfiguration(self::ACTES, $this->openStackConfig);

        $this->assertEquals(
            $openStackContainerManager->getContainerWrapper(self::ACTES),
            $this->openStackContainerWrapperMock
        );
    }
}
