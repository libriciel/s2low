<?php



class OpenStackContainersManagerTest extends PHPUnit_Framework_TestCase {

    /**
     * @throws UnrecoverableException
     */

    public function testExecuteOnUnavailableContainer(){
        $openStackContainerWrapperMock = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackContainerWrapperMock->expects($this->never())
            ->method("execute");

        $openStackContainerWrapperFactoryMock =
            $this->getMockBuilder(OpenStackContainerWrapperFactory::class)
                ->disableOriginalConstructor()
                ->getMock();

        $openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->willReturn($openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainersManager($openStackContainerWrapperFactoryMock);

        $openStackConfig = new OpenStackConfig();
        $openStackConfig->openstack_authentication_url_v3 = "a";
        $openStackConfig->openstack_username = "b";
        $openStackConfig->openstack_password = "c";
        $openStackConfig->openstack_tenant = "d";

        $openStackContainerManager->addConfiguration("actes",$openStackConfig);

        $this->expectException(UnrecoverableException::class);
        $this->expectExceptionMessage("Impossible de trouver la configuration Openstack pour UnavailableContainer");

        $openStackContainerManager->execute("UnavailableContainer","function","options");
    }

	/**
	 * @throws UnrecoverableException
	 */
    public function testExecute(){
		$openStackContainerWrapperMock = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

		$openStackContainerWrapperMock->expects($this->once())
            ->method("execute")
            ->with($this->equalTo("function"),
                $this->equalTo("options"));

		$openStackContainerWrapperFactoryMock =
            $this->getMockBuilder(OpenStackContainerWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

		$openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->willReturn($openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainersManager($openStackContainerWrapperFactoryMock);

        $openStackConfig = new OpenStackConfig();
        $openStackConfig->openstack_authentication_url_v3 = "a";
        $openStackConfig->openstack_username = "b";
        $openStackConfig->openstack_password = "c";
        $openStackConfig->openstack_tenant = "d";

        $openStackContainerManager->addConfiguration("actes",$openStackConfig);

        $openStackContainerManager->execute("actes","function","options");
    }

    /**
     * @throws UnrecoverableException
     */

    public function testExecuteTwice(){
        $openStackContainerWrapperMock = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackContainerWrapperMock->expects($this->once())
            ->method("execute")
            ->willThrowException(new Exception());

        $openStackContainerWrapperMock->expects($this->once())
            ->method("execute")
            ->with($this->equalTo("function"),
                $this->equalTo("options"));

        $openStackContainerWrapperFactoryMock =
            $this->getMockBuilder(OpenStackContainerWrapperFactory::class)
                ->disableOriginalConstructor()
                ->getMock();

        $openStackContainerWrapperFactoryMock
            ->expects($this->once())
            ->method("getContainerWrapper")
            ->willReturn($openStackContainerWrapperMock);

        $openStackContainerManager = new OpenStackContainersManager($openStackContainerWrapperFactoryMock);

        $openStackConfig = new OpenStackConfig();
        $openStackConfig->openstack_authentication_url_v3 = "a";
        $openStackConfig->openstack_username = "b";
        $openStackConfig->openstack_password = "c";
        $openStackConfig->openstack_tenant = "d";

        $openStackContainerManager->addConfiguration("actes",$openStackConfig);

        $openStackContainerManager->execute("actes","function","options");
    }
}