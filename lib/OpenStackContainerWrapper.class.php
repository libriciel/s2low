<?php

use Monolog\Logger;
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
    /**
     * @var Logger
     */
    private $logger;


    public function __construct(OpenStackContainerFetcher $openStackContainerFetcher, Logger $logger, int $timeBetweenAttempts=1){
        $this->logger = $logger;
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
        $hasValidToken = isset($this->token) && !$this->token->hasExpired();
        if(!$hasValidToken){
            $this->logger->info("[Openstack] Token expiré");
        }
        return ($hasValidToken);
    }


    public function resetConnection(){
        $this->logger->info( "[Openstack] Reset Connection");
        list($this->token,$this->container) = [null,null];
    }

    /**
     * @param $options
     * @return StorageObject
     * @throws Exception
     * @throws Throwable
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
     * @throws Throwable
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
     * @throws Throwable
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
     * @throws Throwable
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
            $doNotWaitBeforeRetry = false;
            try{
                return $function($this->getContainer(),$options);
            } catch (Throwable $e){
                list($message, $doNotWaitBeforeRetry) = $this->processThrowable($e);
                $this->logger->error(
                    "[Openstack][$attempts] $message"
                );

                if(!$doNotWaitBeforeRetry){        //No need to wait if it's only a token problem
                    sleep($this->timeBetweenAttempts);
                }
                //TODO : est-il nécessaire de gérer les attempts en dehors de beanstalk ?
                $attempts++;
                $this->resetConnection();
            }
        } while($attempts < self::NUMBER_OF_ATTEMPTS);
        throw new PausingQueueException("[Openstack] Nombre de tentatives dépassé");
    }

    /**
     * @param $e
     * @return array
     */
    private function processThrowable($e): array
    {
        $doNotWaitBeforeRetry = false;

        if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
            // Erreur 404 rencontrée lorsque le serveur n'est pas accessible
            $message = "Erreur Guzzle : " . $e->getMessage();
        } elseif ($e instanceof \OpenStack\Common\Error\BadResponseError) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 401) {
                // Erreur d'authentification : on se réauthentifie
                $doNotWaitBeforeRetry = true;
                $message = "Erreur d'authentification";
            } else {
                // Pour tout autre type d'erreur, on met la queue en pause
                $message = "Erreur $statusCode : " . $e->getResponse()->getReasonPhrase();
            }
        } else {
            $ExceptionClass = get_class($e);
            $messageThrowable = $e->getMessage();
            $message = "Erreur $ExceptionClass : $messageThrowable";
        }
        return array($message, $doNotWaitBeforeRetry);
    }
}