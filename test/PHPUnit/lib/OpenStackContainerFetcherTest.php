<?php

use S2lowLegacy\Lib\OpenStackContainerFetcher;
use OpenStack\Identity\v3\Models\Token;
use OpenStack\Identity\v3\Service;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\OpenStack;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class OpenStackContainerFetcherTest extends TestCase
{
    /**
     * @throws Exception
     */

    public function testgetNewTokenAndContainer()
    {

        $containerFullName = "containerFullName";
        $generate_token_options = ["option" => "option"];

        /** @var MockObject | OpenStack $openStackMock */
        $openStackMock = $this->getMockBuilder(OpenStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->getMock();

        $identityMock = $this->getMockBuilder(Service::class)
            ->disableOriginalConstructor()
            ->getMock();

        $identityMock->expects($this->once())
            ->method("generateToken")
            ->with($generate_token_options)
            ->willReturn($tokenMock);

        $openStackMock->expects($this->once())
            ->method('identityV3')
            ->willReturn($identityMock);

        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStoreV1Mock = $this->getMockBuilder(\OpenStack\ObjectStore\v1\Service::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStoreV1Mock->expects($this->once())
            ->method("getContainer")
            ->with($containerFullName)
            ->willReturn($containerMock);

        $openStackMock->expects($this->once())
            ->method('objectStoreV1')
            ->willReturn($openStoreV1Mock);

        $openStackContainerFetcher = new OpenStackContainerFetcher(
            $containerFullName,
            $generate_token_options,
            $openStackMock,
            new NullLogger()
        );

        $this->assertEquals(
            [$tokenMock,$containerMock],
            $openStackContainerFetcher->getNewTokenAndContainer()
        );
    }
}
