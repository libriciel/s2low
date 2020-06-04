<?php

class OpenStackStateManager{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    private $unsuccessfullConsecutiveAttempts = 0;

    private $needToReconnect = false;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function isResetNeeded(){
        return $this->needToReconnect;
    }

    public function declareSuccess(){
        $this->unsuccessfullConsecutiveAttempts = 0;
    }

    public function declareException(Exception $e){
        $this->unsuccessfullConsecutiveAttempts ++;
        $ExceptionClass = get_class($e);
        if($ExceptionClass === \GuzzleHttp\Exception\ConnectException::class){
            // Erreur 404 rencontrée lorsque le serveur n'est pas accessible
            $message = "Erreur Guzzle : " . $e->getMessage();
        }
        elseif ($ExceptionClass === BadResponseError::class) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 401) {
                // Erreur d'authentification : on se réauthentifie
                $waitBeforeRetry = false;
                $message = "Erreur d'authentification";
            } else {
                // Pour tout autre type d'erreur, on met la queue en pause
                $message = "Erreur $statusCode : " . $e->getResponse()->getReasonPhrase();
            }
        } else{
            $messageThrowable = $e->getMessage();
            $message = "Erreur $ExceptionClass : $messageThrowable";
        }

        $message = $this->shorten($message);
        $this->logger->error(
            "[Openstack][$this->unsuccessfullConsecutiveAttempts] $message"
        );
    }

    private function shorten($message){
        $lgMax= 1000;
        if(strlen($message) > $lgMax){
            $message = substr($message, 0, $lgMax)."...";
        }
        return $message;
    }
}