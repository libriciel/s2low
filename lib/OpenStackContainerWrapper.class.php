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
     * @throws Throwable
     */

    private function executeCommand( $function, $options){
        $attempts = 0;
        do{
            $tokenProblem = false;
            try{
                return $function($this->getContainer(),$options);
            } catch (Throwable $e){
                echo "-----------------------------------------------------------------------\n";
                var_dump($e->getMessage());
                echo get_class($e);
                die();
                echo "-----------------------------------------------------------------------\n";
                $retour=[];
                if($e->getMessage() === "cURL error 6: Could not resolve host: autherreur.cloud.ovh.net (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)"){
                    echo "premier message\n";
                }
                if($e instanceof \OpenStack\Common\Error\BadResponseError){
                    preg_match('/The remote server returned a \"(?<ErrorCode>[0-9][0-9][0-9]) (?<Message>.*)\" error for the following transaction:/',
                        $e->getMessage(),
                        $matches
                    );
                    var_dump($matches['ErrorCode']);
                    if($matches['ErrorCode'] === "401"){
                        $tokenProblem = true;
                        echo "401\n";
                        die();
                    }
                    else{
                        $message = $e->getMessage();
                        // TODO : Le message contient le fichier : le tronquer ici plutot que dans WorkerScriptClass ?
                        throw new PausingQueueException($message);
                    }
                }
                echo "-----------------------------------------------------------------------\n";
                var_dump($e->getMessage());
                echo get_class($e);
                die();
                echo "-----------------------------------------------------------------------\n";
                if(!$tokenProblem){        //No need to wait if it's only a token problem
                    sleep($this->timeBetweenAttempts);
                }
                //TODO : est-il nécessaire de gérer les attempts en dehors de beanstalk ?
                $attempts++;
                $this->resetConnection();
            }
        } while($attempts < self::NUMBER_OF_ATTEMPTS);
        throw $e;
    }
}