<?php

use OpenStack\Common\Error\BadResponseError;
use OpenStack\Identity\v3\Models\Token;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use OpenStack\OpenStack;
use Psr\Http\Message\StreamInterface;

class OpenStackContainerWrapper{
    /** @var string  */
    private $containerFullName;
    /** @var array  */
    private $parametres;
    /** @var OpenStack */
    private $openStack;
    /** @var Token */
    private $token;
    /** @var Container */
    private $container;


    public function __construct( string $containerFullName, array $parametres, $openStack){
        $this->containerFullName = $containerFullName;
        $this->parametres = $parametres;
        $this->openStack = $openStack;
    }

    /**
     * @return Container
     */

    private function getContainer(){
        if((!isset($this->container)) || (!$this->hasValidToken())){
            $this->updateConnection();
        }
        return $this->container;
    }

    /**
     * @return bool
     */

    private function hasValidToken(){
        if(isset($this->token) && !$this->token->hasExpired()){
            return true;
        }
        return false;
    }

    public function resetConnection(){
        $this->token = null;
        $this->container = null;
    }

    private function updateConnection(){
        $this->token =$this->openStack->identityV3()->generateToken($this->parametres);
        $parametresWithToken = $this->parametres;
        $parametresWithToken["cachedToken"]=$this->token->export();

        $this->container = $this->openStack
            ->objectStoreV1($parametresWithToken)
            ->getContainer($this->containerFullName);
    }

    /**
     * @param $function
     * @param $options
     * @return bool|object|StorageObject|StreamInterface|void
     * @throws BadResponseError
     */

    public function execute($function,$options){
        if($function === "createObject"){
            $result = $this->getContainer()->createObject($options);
        }
        elseif ($function === "download"){
            $result = $this->getContainer()->getObject($options)->download();
        }
        elseif ($function === "delete"){
            $result = $this->getContainer()->getObject($options)->delete();
        }
        elseif ($function === "objectExists"){
            $result = $this->getContainer()->objectExists($options);
        }
        else{
            throw new UnexpectedValueException("OpenStackContainerWrapper->execute() : Unknown function ".$function);
        }
        return $result;
    }
}