<?php

class PausingQueueException extends Exception{

    /**
     * Time to wait (in seconds) when this exceptions is thrown
     * @var int
     */
    private $timeToWait;

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getTimeToWait(){
        return $this->timeToWait;
    }
}