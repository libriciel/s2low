<?php

use GuzzleHttp\Exception\ConnectException;
use OpenStack\Common\Error\BadResponseError;

class OpenStackStateManager
{
    public const MAX_CONSECUTIVE_ATTEMPTS = 5;


    private $logger;

    private $unsuccessfullConsecutiveAttempts = 0;


    private $messages = [];

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function isResetNeeded()
    {
        return $this->unsuccessfullConsecutiveAttempts > 0;
    }

    public function declareSuccess()
    {
        $this->unsuccessfullConsecutiveAttempts = 0;
        $this->messages = [];
    }

    public function declareException(Exception $e)
    {
        $this->unsuccessfullConsecutiveAttempts ++;
        $message = $this->processException($e);
        $this->logger->error(
            "[Openstack][$this->unsuccessfullConsecutiveAttempts] $message"
        );
        if ($this->unsuccessfullConsecutiveAttempts > self::MAX_CONSECUTIVE_ATTEMPTS) {
            throw new PausingQueueException("[Openstack] Nombre de tentatives dépassé");
        }
    }

    private function shorten($message)
    {
        $lgMax = 1000;
        if (mb_strlen($message) > $lgMax) {
            $message = mb_substr($message, 0, $lgMax) . "...";
        }
        return $message;
    }

    /**
     * @param Exception $e
     * @return string
     */
    private function processException(Exception $e): string
    {
        $ExceptionClass = get_class($e);
        if ($ExceptionClass === ConnectException::class) {
            // Erreur 404 rencontrée lorsque le serveur n'est pas accessible
            $message = "Erreur Guzzle : " . $e->getMessage();
        } elseif ($ExceptionClass === BadResponseError::class) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 401) {
                // Erreur d'authentification : on se réauthentifie
                $message = "Erreur d'authentification";
            } else {
                $message = "Erreur $statusCode : " . $e->getResponse()->getReasonPhrase();
            }
        } else {
            $messageThrowable = $e->getMessage();
            $message = "Erreur $ExceptionClass : $messageThrowable";
        }
        $message = $this->shorten($message);
        return $message;
    }
}
