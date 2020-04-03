<?php

use OpenStack\Identity\v3\Models\Token;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use Psr\Http\Message\StreamInterface;

class OpenStackContainerWrapper{
    /** @var Token */
    private $token;
    /** @var Container */
    private $container;
    /** @var OpenStackContainerFetcher */
    private $openStackContainerFetcher;

    /** @var int  */
    private $timeBetweenAttempts;

    const NUMBER_OF_ATTEMPTS = 5;


    public function __construct(OpenStackContainerFetcher $openStackContainerFetcher,int $timeBetweenAttempts=1){
        $this->timeBetweenAttempts=$timeBetweenAttempts;
        $this->openStackContainerFetcher = $openStackContainerFetcher;
    }

    /**
     * @return Container
     */

    private function getContainer(){
        if((!isset($this->container)) || (!$this->hasValidToken())){
            list($this->token,$this->container) = $this->openStackContainerFetcher->getNewTokenAndContainer();
        }
        return $this->container;
    }

    /**
     * @return bool
     */

    private function hasValidToken(){
        return (isset($this->token) && !$this->token->hasExpired());
    }


    public function resetConnection(){
        list($this->token,$this->container) = [null,null];
    }

    /**
     * @param $options
     * @return StorageObject
     * @throws Exception
     */

    public function createObject($options){
        return $this->executeCommand(
            function (Container $container,$options){
                return $container->createObject($options);
            },
            $options
        );
    }

    /**
     * @param $options
     * @return StreamInterface
     * @throws Exception
     */
    public function download($options){
        return $this->executeCommand(
            function (Container $container,$options){
                return $container->getObject($options)->download();
            },
            $options
        );
    }

    /**
     * @param $options
     * @return mixed
     * @throws Exception
     */

    public function delete($options){
        return $this->executeCommand(
            function (Container $container,$options){
                return $container->getObject($options)->delete();
            },
            $options
        );
    }

    /**
     * @param $options
     * @return bool
     * @throws Exception
     */

    public function objectExists($options){
        return $this->executeCommand(
            function(Container $container,$options){
                return $container->objectExists($options);
            },
            $options
        );
    }

    /**
     * @param $function
     * @param $options
     * @return mixed
     * @throws Exception
     */

    private function executeCommand( $function, $options){
        $attempts = 0;
        do{
            try{
                return $function($this->getContainer(),$options);
            } catch (Exception $e){
                if($attempts>0){        //No need to wait if it's only a token problem
                    sleep($this->timeBetweenAttempts);
                }
                $attempts++;
                $this->resetConnection();
            }
        } while($attempts < self::NUMBER_OF_ATTEMPTS);
        throw $e;
    }
}