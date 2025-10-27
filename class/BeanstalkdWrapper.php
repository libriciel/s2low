<?php

namespace S2lowLegacy\Class;

use Pheanstalk\PheanstalkInterface;
use Pheanstalk\Pheanstalk;
use Exception;
use Psr\Log\LoggerInterface;

class BeanstalkdWrapper
{
    private const DEFAULT_TTR = 60; /* 60 secondes * 20 */

    private $beanstalkd_server;
    private $beanstalkd_port;

    private $logger;

    public function __construct($beanstalkd_server, $beanstalkd_port, LoggerInterface $s2lowLogger)
    {
        $this->beanstalkd_server = $beanstalkd_server;
        $this->beanstalkd_port = $beanstalkd_port;
        $this->logger = $s2lowLogger;
    }

    public function put(
        $queue_name,
        $data,
        $delay = PheanstalkInterface::DEFAULT_DELAY,
        $ttr = PheanstalkInterface::DEFAULT_TTR
    ) {
        $queue = "undefined";
        try {
            $queue = new Pheanstalk($this->beanstalkd_server, $this->beanstalkd_port);
            $queue->useTube($queue_name)->put(
                $data,
                PheanstalkInterface::DEFAULT_PRIORITY,
                $delay,
                $ttr
            );
        } catch (Exception $e) {
            $this->logger->critical(
                "Unable to send data to queue : " . $e->getMessage(),
                ['queue' => $queue,'data' => $data]
            );
            return false;
        }
        return true;
    }

    /**
     * @param $queue_name
     * @return Pheanstalk
     */
    public function getQueue($queue_name)
    {
        $queue = new Pheanstalk($this->beanstalkd_server);
        $queue->watch($queue_name);
        return $queue;
    }


    public function emptyQueue($queue_name)
    {
        $queue = new Pheanstalk($this->beanstalkd_server);
        $queue->watch($queue_name);
        while ($job = $queue->reserve(0)) {
            $queue->delete($job);
        }
        return true;
    }
}
