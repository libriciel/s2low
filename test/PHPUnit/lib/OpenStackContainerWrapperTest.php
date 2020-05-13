<?php

use GuzzleHttp\Exception\ConnectException;
use Monolog\Logger;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\Identity\v3\Models\Token;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamInterface;

class OpenStackContainerWrapperTest extends PHPUnit\Framework\TestCase {

    private function getTokenMock(bool $isExpired=false){
        $tokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenMock->expects($this->any())
            ->method("hasExpired")
            ->willReturn($isExpired);

        return $tokenMock;
    }

    private function getContainerMock(){
        return $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getOpenStackContainerFetcherMock($expects,$willReturn){
        /** @var  $openStackContainerFetcherMock OpenStackContainerFetcher | MockObject */
        $openStackContainerFetcherMock = $this->getMockBuilder(OpenStackContainerFetcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackContainerFetcherMock->expects($expects)
            ->method('getNewTokenAndContainer')
            ->will($willReturn);

        return $openStackContainerFetcherMock;
    }

    public function testExecuteCreateObject(){
        $method = 'createObject';
        $willReturn=$this->getMockBuilder(StorageObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $options= ["options"];

        $methodCalled=$method;

        $containerMock = $this->getContainerMock();

        $containerMock->expects($this->once())
            ->method($methodCalled)
            ->willReturn($willReturn);

        $openStackContainerFetcherMock = $this->getOpenStackContainerFetcherMock(
            $this->once(),
            $this->returnValue([$this->getTokenMock(),$containerMock]
            )
        );

        $openStackContainerWrapper = new OpenStackContainerWrapper($openStackContainerFetcherMock,
            new Logger("test")
        );

        $this->assertEquals(
            $openStackContainerWrapper->$method($options),
            $willReturn
        );
    }

    /**
     * @throws Exception
     */

    public function testExecuteDownload(){
        /** @var $streamInterfaceMock StreamInterface  */
        $streamInterfaceMock = $this->getMockBuilder(StreamInterface::class)
        ->disableOriginalConstructor()
        ->getMock();

        $StorageObjetMock=$this->getMockBuilder(StorageObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $StorageObjetMock->expects($this->once())
            ->method('download')
            ->willReturn($streamInterfaceMock);

        $containerMock = $this->getContainerMock();

        $containerMock->expects($this->once())
            ->method('getObject')
            ->willReturn($StorageObjetMock);

        $openStackContainerFetcherMock = $this->getOpenStackContainerFetcherMock(
            $this->once(),
            $this->returnValue([$this->getTokenMock(),$containerMock])
        );

        $openStackContainerWrapper = new OpenStackContainerWrapper($openStackContainerFetcherMock,
            new Logger("test")
        );

        $this->assertEquals(
            $openStackContainerWrapper->download("options"),
            $streamInterfaceMock
        );
    }

    /**
     * @throws Exception
     */

    public function testExecuteObjectExists(){
        $willReturn= true;
        $options="options";

        $containerMock = $this->getContainerMock();

        $containerMock->expects($this->once())
            ->method('objectExists')
            ->with($this->equalTo($options))
            ->willReturn($willReturn);

        $openStackContainerFetcherMock = $this->getOpenStackContainerFetcherMock(
            $this->once(),
            $this->returnValue([$this->getTokenMock(),$containerMock])
        );

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            $openStackContainerFetcherMock,
            new Logger("test")
        );

        $this->assertEquals(
             $openStackContainerWrapper->objectExists($options),
             $willReturn
        );
    }

    /**
     * @throws Exception
     */

    public function testExecuteDelete(){
        $options="options";

        $storageObjectNameMock=$this->getMockBuilder(StorageObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storageObjectNameMock->expects($this->any())->method('delete')->willReturn(true);

        $containerMock = $this->getContainerMock();

        $containerMock->expects($this->once())
            ->method('getObject')
            ->with($this->equalTo($options))
            ->willReturn($storageObjectNameMock);

        $openStackContainerFetcherMock = $this->getOpenStackContainerFetcherMock(
            $this->once(),
            $this->returnValue([$this->getTokenMock(),$containerMock])
        );

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            $openStackContainerFetcherMock,
            new Logger("test")
        );

        $this->assertEquals(
            $openStackContainerWrapper->delete($options),
           true
        );
    }

    /**
     * @throws Exception
     */

    public function testExecuteFunctionOnContainerWithOutdatedToken(){

        $openStackContainerFetcherMock = $this->getOpenStackContainerFetcherMock(
            $this->exactly(2),
            $this->onConsecutiveCalls(
                [$this->getTokenMock(true),$this->getContainerMock()],
                [$this->getTokenMock(false),$this->getContainerMock()]
            )
        );

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            $openStackContainerFetcherMock,
            new Logger("test"),
            0
        );

        // Première connexion : le token est périmé mais le getNewTokenAndContainer renvoie
        // quand même un container
        $openStackContainerWrapper->createObject(["options"]);
        // Deuxième connexion : le token périmé est détecté. Nouvelle appel à
        // openStackContainerFetcher
        // Première éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->createObject(["options"]);
        //Troisième connexion : le token est ok. Aucun appel à openStackContainerFetcher
        // Deuxième éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->createObject(["options"]);
    }

    /**
     * @throws Exception
     */

    public function testResetConnexion(){

        $firstContainerMock = $this->getContainerMock();

        $firstContainerMock->expects($this->once())
            ->method('createObject')
            ->with(["options1"]);

        $secondContainerMock = $this->getContainerMock();

        $secondContainerMock->expects($this->exactly(2))
            ->method('createObject')
            ->with(["options2"]);

        $openStackContainerFetcherMock = $this->getOpenStackContainerFetcherMock(
            $this->exactly(2),
            $this->onConsecutiveCalls(
                [$this->getTokenMock(),$firstContainerMock],
                [$this->getTokenMock(),$secondContainerMock]
            )
        );

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            $openStackContainerFetcherMock,
            new Logger("test"),
            0
        );

        // Première connexion : premier appel à identityV3 et objectStoreV1
        $openStackContainerWrapper->createObject(["options1"]);
        $openStackContainerWrapper->resetConnection();
        // Deuxième connexion : deuxième appel à identityV3 et objectStoreV1
        // Première éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->createObject(["options2"]);
        //Troisième connexion : le token en ok. Aucun appel à identityV3
        // et objectStoreV1
        // Deuxième éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->createObject(["options2"]);
    }

    /**
     * @throws Exception
     */

    public function testExecuteFailsUntilTheEnd(){

        $openStackContainerFetcherMock = $this->getOpenStackContainerFetcherMock(
            $this->exactly(5),
            $this->onConsecutiveCalls(
                $this->throwException(new BadMethodCallException("Exception1")),
                $this->throwException(new BadMethodCallException("Exception2")),
                $this->throwException(new BadMethodCallException("Exception3")),
                $this->throwException(new BadMethodCallException("Exception4")),
                $this->throwException(new BadMethodCallException("Exception5"))
            )
        );

        /** @var Logger|MockObject $logger */
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger->expects($this->exactly(5))
            ->method("error");

        $logger->expects($this->exactly(5))
            ->method("info");

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            $openStackContainerFetcherMock,
            $logger,
            0
        );

        $this->expectException(PausingQueueException::class);
        $this->expectExceptionMessage("[Openstack] Nombre de tentatives dépassé");

        $openStackContainerWrapper->objectExists("ObjetTest");
    }

    /**
     * @throws Exception
     */

    public function testEachPossibleException(){

        $request = new \GuzzleHttp\Psr7\Request("method","uri");
        $firstException = new ConnectException("First Exception", $request);

        $secondException = new BadResponseError("Second Exception",
            401
        );
        $response2 = new \GuzzleHttp\Psr7\Response(401,[]);
        $secondException->setResponse($response2);

        $thirdException = new BadResponseError("Third Exception",
            500
        );
        $response3 = new \GuzzleHttp\Psr7\Response(500,[]);
        $thirdException->setResponse($response3);

        $FourthException = new Exception("Fourth Exception");

        $openStackContainerFetcherMock = $this->getOpenStackContainerFetcherMock(
            $this->exactly(5),
            $this->onConsecutiveCalls(
                $this->throwException($firstException),
                $this->throwException($secondException),
                $this->throwException($thirdException),
                $this->throwException($FourthException),
                $this->returnValue(null)
            )
        );

        /** @var Logger|MockObject $logger */
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger->expects($this->exactly(5))
            ->method("error");

        $logger->expects($this->at(0))
            ->method("error")
            ->with("[Openstack][0] Erreur Guzzle : First Exception");

        $logger->expects($this->at(2))
            ->method("error")
            ->with("[Openstack][1] Erreur d'authentification");

        $logger->expects($this->at(4))
            ->method("error")
            ->with("[Openstack][2] Erreur 500 : Internal Server Error");

        $logger->expects($this->at(6))
            ->method("error")
            ->with("[Openstack][3] Erreur Exception : Fourth Exception");

        $logger->expects($this->exactly(5))
            ->method("info");

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            $openStackContainerFetcherMock,
            $logger,
            0
        );

        $this->expectException(PausingQueueException::class);
        $this->expectExceptionMessage("[Openstack] Nombre de tentatives dépassé");

        $openStackContainerWrapper->objectExists("ObjetTest");
    }
}