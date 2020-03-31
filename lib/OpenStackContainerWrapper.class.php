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
    private $generate_token_options;
    /** @var OpenStack */
    private $openStack;
    /** @var Token */
    private $token;
    /** @var Container */
    private $container;

    const NUMBER_OF_ATTEMPTS = 5;


    public function __construct(string $containerFullName, array $generate_token_options, OpenStack $openStack){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);

        $this->containerFullName = $containerFullName;
        $this->generate_token_options = $generate_token_options;
        $this->openStack = $openStack;
    }

    /**
     * @return Container
     */

    private function getContainer(){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        //echo "getContainer\n";
        //echo 'isset($this->container)';
        //var_dump(isset($this->container));
        //echo '$this->hasValidToken()\n';
        //var_dump($this->hasValidToken());
        if((!isset($this->container)) || (!$this->hasValidToken())){
            $this->updateConnection();
        }
        return $this->container;
    }

    /**
     * @return bool
     */

    private function hasValidToken(){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        //echo "hasValidToken";
        //echo 'isset($this->token)';
        //var_dump(isset($this->token));
        //if(isset($this->token)){
        //    echo '$this->token->hasExpired()';
        //    var_dump($this->token->hasExpired());
        //}

        return (isset($this->token) && !$this->token->hasExpired());
    }

    public function resetConnection(){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        $this->token = null;
        $this->container = null;
    }

    private function updateConnection(){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        //echo "updateConnection------------------------------------------------------------------------\n";
        $this->token =$this->openStack->identityV3()->generateToken($this->generate_token_options);
        //echo "1---------------------------------------------------------------------------------------\n";
        $parametresWithToken = $this->generate_token_options;
        $parametresWithToken["cachedToken"]=$this->token->export();
        //echo "2---------------------------------------------------------------------------------------\n";

        $this->container = $this->openStack
            ->objectStoreV1($parametresWithToken)
            ->getContainer($this->containerFullName);
        //echo "FinUpdate----------------------------------------------------------------------------------\n";
        //var_dump($this->hasValidToken());
        //var_dump(isset($this->container));
        //var_dump($this->hasValidToken());
        //var_dump(isset($this->container));
        //echo "FinTestUpdate------------------------------------------------------------------------------\n";
    }

    /**
     * @param $function
     * @param $options
     * @return bool|object|StorageObject|StreamInterface|void
     * @throws BadResponseError
     */

    private function createObjectCommand(Container $container,$options){
        echo __FUNCTION__."--------------------------\n";
        var_dump($options);
        return $container->createObject($options);
    }

    private function downloadCommand(Container $container,$options){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        return $container->getObject($options)->download();
    }

    private function deleteCommand(Container $container,$options){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        return $container->getObject($options)->delete();
    }

    private function objectExistsCommand(Container $container,$options){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        return $container->objectExists($options);
    }

    public function createObject($options){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        $this->executeCommand('createObjectCommand',$options);
    }

    public function download($options){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        $this->executeCommand('downloadCommand',$options);
    }

    public function delete($options){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        $this->executeCommand('deleteCommand',$options);
    }

    public function objectExists($options){
        echo __FUNCTION__."--------------------------\n";
        var_dump($this->token);
        $this->executeCommand('objectExistsCommand',$options);
    }

    /**
     * @param $function
     * @param $options
     * @return mixed
     * @throws Exception
     */

    private function executeCommand($function, $options){
        echo __FUNCTION__."--------------------------\n";
        var_dump($function);
        var_dump($options);
        $attempts = 0;
        do{
            try{
                return $this->$function($this->getContainer(),$options);
            } catch (Exception $e){
                var_dump($e->getMessage());
                die();
                if($attempts>0){        //No need to wait if it's only a token problem
                    sleep(1);
                }
                $attempts++;
                $this->resetConnection();
            }
        } while($attempts < self::NUMBER_OF_ATTEMPTS);
        throw $e;
    }
}