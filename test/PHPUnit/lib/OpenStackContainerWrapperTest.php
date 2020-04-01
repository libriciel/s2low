<?php

use OpenStack\Identity\v3\Models\Token;
use OpenStack\Identity\v3\Service;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use OpenStack\OpenStack;

class OpenStackContainerWrapperTest extends PHPUnit\Framework\TestCase {

    private $openStackMock;

    private $parametres;

    public function setUp(): void
    {
        $this->parametres = [
            "authUrl"=>"authUrl",
            "region" => "region",
            "user" => [
                'name' => 'name',
                'password' => 'password',
                'domain' => ['name' => 'Default']
            ]
        ];

        $this->openStackMock = $this->getMockBuilder(OpenStack::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getIdentityService(bool $hasExpired){

        $tokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenMock->method("hasExpired")->willReturn($hasExpired);

        $identityMock = $this->getMockBuilder(Service::class)
            ->disableOriginalConstructor()
            ->getMock();

        $identityMock->expects($this->once())
            ->method("generateToken")
            ->with($this->parametres)
            ->willReturn($tokenMock);

        return $identityMock;
    }

    private function getObjectStore($numberOfContainerMethodCalls=1,
                                    $calledContainerMethod='createObject',
                                    $willReturn=null,
                                    $options=["options"]){

        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        var_dump($calledContainerMethod);

        if(!is_null($willReturn)){
            $containerMock->expects($this->exactly($numberOfContainerMethodCalls))
                ->method($calledContainerMethod)
                ->with($options)
                ->willReturn($willReturn);
        }
        else{
            $containerMock->expects($this->exactly($numberOfContainerMethodCalls))
                ->method($calledContainerMethod)
                ->with($options);
        }

        $openStoreV1Mock = $this->getMockBuilder(\OpenStack\ObjectStore\v1\Service::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStoreV1Mock->expects($this->once())
            ->method("getContainer")
            ->with("ContainerName")
            ->willReturn($containerMock);

        return $openStoreV1Mock;
    }

    //Todo : changer les fonctions appelées //

    /**
     * @dataProvider providerExecuteContainerMethod
     * @param $method
     */

    public function testExecuteContainerMethod($method,$willReturn,$options){
        $methodCalled=$method;
        if(in_array($method,['delete','download'])){
            $methodCalled='getObject';
        }
        $this->openStackMock->expects($this->once())
            ->method("identityV3")
            ->willReturn($this->getIdentityService(false));

        $this->openStackMock->expects($this->once())
            ->method("objectStoreV1")
            ->willReturn($this->getObjectStore(1,$methodCalled,$willReturn,$options));

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            "ContainerName",
            $this->parametres,
            $this->openStackMock);

        $openStackContainerWrapper->$method($options);
    }

    public function providerExecuteContainerMethod(){

        $StorageObjectMock0=$this->getMockBuilder(StorageObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $StorageObjectMock1=$this->getMockBuilder(StorageObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $StorageObjectMock1->expects($this->once())->method('download');

        $StorageObjectMock2=$this->getMockBuilder(StorageObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $StorageObjectMock2->expects($this->any())->method('delete');

        return [['createObject',$StorageObjectMock0,["options"]],
                ['download',$StorageObjectMock1,"options"],
                ['delete',$StorageObjectMock2,"options"],
                ['objectExists',true,"options"]
        ];
    }

    public function testExecuteFunctionOnContainerWithOutdatedToken(){
        $this->openStackMock->expects($this->exactly(2))
            ->method("identityV3")
            ->will(
                $this->onConsecutiveCalls(
                    $this->getIdentityService(true),
                    $this->getIdentityService(false)
                ));

        $this->openStackMock->expects($this->exactly(2))
            ->method("objectStoreV1")
            ->will(
                $this->onConsecutiveCalls(
                    $this->getObjectStore(),
                    $this->getObjectStore(2)
                ));

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            "ContainerName",
            $this->parametres,
            $this->openStackMock);

        // Première connexion : le token est périmé mais le getContainer renvoie
        // quand même un container
        $openStackContainerWrapper->createObject(["options"]);
        // Deuxième connexion : le token périmé est détecté. Nouvelle connexion
        // à identityV3 et objectStoreV1
        // Première éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->createObject(["options"]);
        //Troisième connexion : le token en ok. Aucun appel à identityV3
        // et objectStoreV1
        // Deuxième éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->createObject(["options"]);
    }

    /**
     * @throws \OpenStack\Common\Error\BadResponseError
     */

    public function testResetConnexion(){
        $this->openStackMock->expects($this->exactly(2))
            ->method("identityV3")
            ->will(
                $this->onConsecutiveCalls(
                    $this->getIdentityService(false),
                    $this->getIdentityService(false)
                ));

        $this->openStackMock->expects($this->exactly(2))
            ->method("objectStoreV1")
            ->will(
                $this->onConsecutiveCalls(
                    $this->getObjectStore(),
                    $this->getObjectStore(2)
                ));

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            "ContainerName",
            $this->parametres,
            $this->openStackMock);

        // Première connexion : premier appel à identityV3 et objectStoreV1
        $openStackContainerWrapper->createObject(["options"]);
        $openStackContainerWrapper->resetConnection();
        // Deuxième connexion : deuxième appel à identityV3 et objectStoreV1
        // Première éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->createObject(["options"]);
        //Troisième connexion : le token en ok. Aucun appel à identityV3
        // et objectStoreV1
        // Deuxième éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->createObject(["options"]);
    }


//

    /**
     * @throws UnrecoverableException
     */

  /*  public function testExecuteTwice(){

        /*$this->openStackContainerWrapperMock
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

        $openStackContainerManager = new OpenStackContainersStore($this->openStackContainerWrapperFactoryMock);
        $openStackContainerManager->addConfiguration(self::ACTES,$this->openStackConfig);
        $result = $openStackContainerManager->execute(self::ACTES, self::FUNCTION1, self::OPTIONS);
        //$this->assertEquals(true,$result);*/
    //}

    /**
     * @throws UnrecoverableException
     */

    public function testExecuteFailsUntilTheEnd(){

        $this->openStackMock->expects($this->exactly(5))
            ->method("identityV3")
            ->will(
                $this->onConsecutiveCalls(
                    $this->getIdentityService(true),
                    $this->getIdentityService(true),
                    $this->getIdentityService(true),
                    $this->getIdentityService(true),
                    $this->getIdentityService(true)
                ));

        $this->openStackMock->expects($this->exactly(5))
            ->method("objectStoreV1")
            ->will(
                $this->onConsecutiveCalls(
                    $this->throwException(new BadMethodCallException("Exception1")),
                    $this->throwException(new BadMethodCallException("Exception2")),
                    $this->throwException(new BadMethodCallException("Exception3")),
                    $this->throwException(new BadMethodCallException("Exception4")),
                    $this->throwException(new BadMethodCallException("Exception5"))
                ));

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            "ContainerName",
            $this->parametres,
            $this->openStackMock);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Exception5");

        $openStackContainerWrapper->objectExists("ObjetTest");
    }
}