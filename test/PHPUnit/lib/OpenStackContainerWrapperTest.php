<?php

use OpenStack\Identity\v3\Models\Token;
use OpenStack\Identity\v3\Service;
use OpenStack\ObjectStore\v1\Models\Container;
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

    private function getObjectStore($numberOfExecutions=1){
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $containerMock->expects($this->exactly($numberOfExecutions))
            ->method("createObject")
            ->with(["Options"]);

        $openStoreV1Mock = $this->getMockBuilder(\OpenStack\ObjectStore\v1\Service::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStoreV1Mock->expects($this->once())
            ->method("getContainer")
            ->with("ContainerName")
            ->willReturn($containerMock);

        return $openStoreV1Mock;
    }

    /**
     * @throws \OpenStack\Common\Error\BadResponseError
     */
    public function testExecuteUnknownFunction(){

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            "ContainerName",
            ['options'],
            $this->openStackMock);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("OpenStackContainerWrapper->execute() : Unknown function function");
        $openStackContainerWrapper->execute("function","Options");
    }

    public function testExecuteKnownFunction(){
        $this->openStackMock->expects($this->once())
            ->method("identityV3")
            ->willReturn($this->getIdentityService(false));

        $this->openStackMock->expects($this->once())
            ->method("objectStoreV1")
            ->willReturn($this->getObjectStore());

        $openStackContainerWrapper = new OpenStackContainerWrapper(
            "ContainerName",
            $this->parametres,
            $this->openStackMock);

        $openStackContainerWrapper->execute("createObject",["Options"]);
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
        $openStackContainerWrapper->execute("createObject",["Options"]);
        // Deuxième connexion : le token périmé est détecté. Nouvelle connexion
        // à identityV3 et objectStoreV1
        // Première éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->execute("createObject",["Options"]);
        //Troisième connexion : le token en ok. Aucun appel à identityV3
        // et objectStoreV1
        // Deuxième éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->execute("createObject",["Options"]);
    }

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
        $openStackContainerWrapper->execute("createObject",["Options"]);
        $openStackContainerWrapper->resetConnection();
        // Deuxième connexion : deuxième appel à identityV3 et objectStoreV1
        // Première éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->execute("createObject",["Options"]);
        //Troisième connexion : le token en ok. Aucun appel à identityV3
        // et objectStoreV1
        // Deuxième éxécution de createObject sur la deuxième instance de
        // $containerMock
        $openStackContainerWrapper->execute("createObject",["Options"]);
    }
}