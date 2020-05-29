<?php

use GuzzleHttp\Exception\ConnectException;
use Monolog\Logger;
use OpenStack\Common\Error\BadResponseError;
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
    private const timeToWait = 100;
    /**
     * @var Logger
     */
    private $logger;


    public function __construct(OpenStackContainerFetcher $openStackContainerFetcher, \Psr\Log\LoggerInterface $logger, int $timeBetweenAttempts=1){
        $this->logger = $logger;
        $this->timeBetweenAttempts=$timeBetweenAttempts;
        $this->openStackContainerFetcher = $openStackContainerFetcher;
    }

    /**
     * @return Container
     * @throws Exception
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
     * @throws PausingQueueException
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
     * @throws PausingQueueException
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
     * @throws PausingQueueException
     */

    public function delete($options){
        return $this->executeCommand(
            function (Container $container,$options){
                $container->getObject($options)->delete();
                return true;
            },
            $options
        );
    }

    /**
     * @param $options
     * @return bool
     * @throws PausingQueueException
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
     * @throws PausingQueueException
     */

    private function executeCommand( callable $function, $options){
        $attempts = 0;
        do{
            $message = "";
            $doNotWaitBeforeRetry = false;
            try{
                return $function($this->getContainer(),$options);
            } catch (ConnectException $e){
                $doNotWaitBeforeRetry = false;
                // Erreur 404 rencontrée lorsque le serveur n'est pas accessible
                $message = "Erreur Guzzle : " . $e->getMessage();
            } catch( BadResponseError $e) {
                $statusCode = $e->getResponse()->getStatusCode();
                if ($statusCode === 401) {
                    // Erreur d'authentification : on se réauthentifie
                    $doNotWaitBeforeRetry = true;
                    $message = "Erreur d'authentification";
                } else {
                    // Pour tout autre type d'erreur, on met la queue en pause
                    $message = "Erreur $statusCode : " . $e->getResponse()->getReasonPhrase();
                }
            } catch (Exception $e) {
                $ExceptionClass = get_class($e);
                $messageThrowable = $e->getMessage();
                $message = "Erreur $ExceptionClass : $messageThrowable";
            }
            $message = $this->shorten($message);
            $this->logger->error(
        "[Openstack][$attempts] $message"
            );

            if(!$doNotWaitBeforeRetry){        //No need to wait if it's only a token problem
                sleep($this->timeBetweenAttempts);
            }
            $attempts++;
            $this->resetConnection();
        } while($attempts < self::NUMBER_OF_ATTEMPTS);
        throw new PausingQueueException("[Openstack] Nombre de tentatives dépassé", self::timeToWait);
    }

    private function shorten($message){
        $lgMax= 1000;
        if(strlen($message) > $lgMax){
            $message = substr($message, 0, $lgMax)."...";
        }
        return $message;
    }
}